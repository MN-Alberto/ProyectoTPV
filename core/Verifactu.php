<?php

/**
 * Clase para la gestión de Verifactu (S.I.F.)
 * Implementa encadenamiento, generación de XML y envío a AEAT.
 * Estructura XML alineada estrictamente con el esquema XSD de la AEAT.
 */
class Verifactu
{
    private static $config = null;

    /**
     * Obtiene un valor de configuración fiscal.
     */
    public static function getConfig($clave, $default = null)
    {
        if (self::$config === null) {
            self::$config = [];
            try {
                require_once(__DIR__ . '/conexionDB.php');
                $pdo = ConexionDB::getInstancia()->getConexion();
                $stmt = $pdo->query("SELECT clave, valor FROM configuracion_fiscal");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    self::$config[$row['clave']] = $row['valor'];
                }
            } catch (Exception $e) {}
        }

        $map = [
            'TPV_NIF' => 'tpv_nif',
            'TPV_RAZON_SOCIAL' => 'tpv_razon_social',
            'TPV_DIRECCION' => 'tpv_direccion',
            'AEAT_URL_VERIFACTU' => 'aeat_url_verifactu',
            'CERT_PATH' => 'cert_path',
            'CERT_PASS' => 'cert_pass'
        ];

        $dbKey = $map[$clave] ?? $clave;
        if (isset(self::$config[$dbKey])) return self::$config[$dbKey];
        return defined($clave) ? constant($clave) : $default;
    }

    /**
     * Devuelve toda la configuración pública para el frontend.
     */
    public static function publicGetConfig()
    {
        if (self::$config === null) {
            self::getConfig('force_init');
        }
        return self::$config;
    }

    public static function getQRBaseUrl()
    {
        $aeatUrl = self::getConfig('AEAT_URL_VERIFACTU', '');
        if (strpos($aeatUrl, 'prewww') !== false) {
            return 'https://prewww2.aeat.es/wlpl/TIKE-CONT/ValidarQR';
        }
        return 'https://www2.agenciatributaria.gob.es/wlpl/TIKE-CONT/ValidarQR';
    }

    public static function getAnulacionUrl()
    {
        $regUrl = self::getConfig('AEAT_URL_VERIFACTU');
        if (strpos($regUrl, 'VerifactuSOAP') !== false) {
            return str_replace('VerifactuSOAP', 'VerifactuAnuSOAP', $regUrl);
        }
        return $regUrl;
    }

    public static function calcularHuellaAlta($nif, $numSerie, $fechaExp, $tipo, $cuota, $importe, $prevHash, $fechaHito)
    {
        // Orden AEAT: NIF + NumSerie + FechaExp + Tipo + Cuota + Importe + PrevHash + Hito
        $str = $nif . $numSerie . $fechaExp . $tipo . $cuota . $importe . ($prevHash ?: '') . $fechaHito;
        return strtoupper(hash('sha256', $str));
    }

    public static function calcularHuellaAnulacion($nif, $numSerie, $fechaExp, $prevHash, $fechaHito)
    {
        // Orden AEAT: NIF + NumSerie + FechaExp + PrevHash + Hito
        $str = $nif . $numSerie . $fechaExp . ($prevHash ?: '') . $fechaHito;
        return strtoupper(hash('sha256', $str));
    }

    public static function calcularHashEncadenado($xmlContent, $hashPrevio)
    {
        $data = $xmlContent . ($hashPrevio ?? '');
        return strtoupper(hash('sha256', $data));
    }

    /**
     * Genera XML de Alta según XSD oficial:
     * RegFactuSistemaFacturacion > Cabecera + RegistroFactura > RegistroAlta
     *
     * RegistroFacturacionAltaType sequence (XSD):
     *   IDVersion, IDFactura{IDEmisorFactura(NIF), NumSerieFactura, FechaExpedicionFactura},
     *   NombreRazonEmisor, TipoFactura, DescripcionOperacion,
     *   Destinatarios?, Desglose, CuotaTotal, ImporteTotal,
     *   Encadenamiento, SistemaInformatico, FechaHoraHusoGenRegistro, TipoHuella, Huella
     */
    public static function generarXML($venta, $lineas)
    {
        $nombre = htmlspecialchars(self::getConfig('TPV_RAZON_SOCIAL'), ENT_XML1, 'UTF-8');
        $nif = htmlspecialchars(self::getConfig('TPV_NIF'), ENT_XML1, 'UTF-8');
        $fechaExp = date('d-m-Y', strtotime($venta->getFecha()));
        $numero = htmlspecialchars($venta->getNumero(), ENT_XML1, 'UTF-8');
        $serie = $venta->getSerie() ? htmlspecialchars($venta->getSerie(), ENT_XML1, 'UTF-8') : '';
        $numSerieSafe = preg_replace('/\s+/', '', $serie . $numero);
        $total = number_format($venta->getTotal(), 2, '.', '');
        $tipoFactura = $venta->getClienteDni() ? 'F1' : 'F2';
        $fechaHito = date('Y-m-d\TH:i:sP');

        $desgloseIVA = [];
        $cuotaTotalTax = 0;
        foreach ($lineas as $linea) {
            // Support both LineaVenta objects and assoc arrays from fetchAll
            $rate = is_object($linea) ? (float)($linea->getIva() ?? 21) : (float)($linea['iva'] ?? 21);
            $base = is_object($linea) ? (float)$linea->getSubtotal() : (float)($linea['subtotal'] ?? 0);
            if (!isset($desgloseIVA["$rate"])) $desgloseIVA["$rate"] = ['base' => 0, 'cuota' => 0];
            $lCuota = $base * ($rate / 100);
            $desgloseIVA["$rate"]['base'] += $base;
            $desgloseIVA["$rate"]['cuota'] += $lCuota;
            $cuotaTotalTax += $lCuota;
        }
        $cuotaFmt = number_format($cuotaTotalTax, 2, '.', '');
        $prevHash = ($venta->getHashPrevio() && $venta->getHashPrevio() !== 'INITIAL_HASH_PLACEHOLDER') ? $venta->getHashPrevio() : '';
        $huella = self::calcularHuellaAlta($nif, $numSerieSafe, $fechaExp, $tipoFactura, $cuotaFmt, $total, $prevHash, $fechaHito);

        // --- Root: RegFactuSistemaFacturacion ---
        $xml = "    <sum:RegFactuSistemaFacturacion\n";
        $xml .= "        xmlns:sum=\"https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroLR.xsd\"\n";
        $xml .= "        xmlns:sum1=\"https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd\">\n";

        // --- Cabecera (CabeceraType: ObligadoEmision{NombreRazon, NIF}) ---
        $xml .= "      <sum:Cabecera>\n";
        $xml .= "        <sum1:ObligadoEmision>\n";
        $xml .= "          <sum1:NombreRazon>" . $nombre . "</sum1:NombreRazon>\n";
        $xml .= "          <sum1:NIF>" . $nif . "</sum1:NIF>\n";
        $xml .= "        </sum1:ObligadoEmision>\n";
        $xml .= "      </sum:Cabecera>\n";

        // --- RegistroFactura > RegistroAlta (choice in XSD) ---
        $xml .= "      <sum:RegistroFactura>\n";
        $xml .= "        <sum1:RegistroAlta>\n";

        // IDVersion
        $xml .= "          <sum1:IDVersion>1.0</sum1:IDVersion>\n";

        // IDFactura (IDFacturaExpedidaType: IDEmisorFactura=NIF, NumSerieFactura, FechaExpedicionFactura)
        $xml .= "          <sum1:IDFactura>\n";
        $xml .= "            <sum1:IDEmisorFactura>" . $nif . "</sum1:IDEmisorFactura>\n";
        $xml .= "            <sum1:NumSerieFactura>" . $numSerieSafe . "</sum1:NumSerieFactura>\n";
        $xml .= "            <sum1:FechaExpedicionFactura>" . $fechaExp . "</sum1:FechaExpedicionFactura>\n";
        $xml .= "          </sum1:IDFactura>\n";

        // NombreRazonEmisor
        $xml .= "          <sum1:NombreRazonEmisor>" . $nombre . "</sum1:NombreRazonEmisor>\n";

        // TipoFactura
        $xml .= "          <sum1:TipoFactura>" . $tipoFactura . "</sum1:TipoFactura>\n";

        // DescripcionOperacion (OBLIGATORIO en XSD)
        $xml .= "          <sum1:DescripcionOperacion>Venta</sum1:DescripcionOperacion>\n";

        // Destinatarios (solo para F1 con cliente identificado)
        if ($tipoFactura === 'F1' && $venta->getClienteDni()) {
            $cliNom = htmlspecialchars($venta->getClienteNombre() ?: 'CLIENTE', ENT_XML1, 'UTF-8');
            $cliNif = htmlspecialchars($venta->getClienteDni(), ENT_XML1, 'UTF-8');
            $xml .= "          <sum1:Destinatarios>\n";
            $xml .= "            <sum1:IDDestinatario>\n";
            $xml .= "              <sum1:NombreRazon>" . $cliNom . "</sum1:NombreRazon>\n";
            $xml .= "              <sum1:NIF>" . $cliNif . "</sum1:NIF>\n";
            $xml .= "            </sum1:IDDestinatario>\n";
            $xml .= "          </sum1:Destinatarios>\n";
        }

        // Desglose (DesgloseType > DetalleDesglose)
        $xml .= "          <sum1:Desglose>\n";
        foreach ($desgloseIVA as $rate => $data) {
            $xml .= "            <sum1:DetalleDesglose>\n";
            $xml .= "              <sum1:ClaveRegimen>01</sum1:ClaveRegimen>\n";
            $xml .= "              <sum1:CalificacionOperacion>S1</sum1:CalificacionOperacion>\n";
            $xml .= "              <sum1:TipoImpositivo>" . number_format((float)$rate, 2, '.', '') . "</sum1:TipoImpositivo>\n";
            $xml .= "              <sum1:BaseImponibleOimporteNoSujeto>" . number_format($data['base'], 2, '.', '') . "</sum1:BaseImponibleOimporteNoSujeto>\n";
            $xml .= "              <sum1:CuotaRepercutida>" . number_format($data['cuota'], 2, '.', '') . "</sum1:CuotaRepercutida>\n";
            $xml .= "            </sum1:DetalleDesglose>\n";
        }
        $xml .= "          </sum1:Desglose>\n";

        // CuotaTotal (OBLIGATORIO en XSD)
        $xml .= "          <sum1:CuotaTotal>" . $cuotaFmt . "</sum1:CuotaTotal>\n";

        // ImporteTotal
        $xml .= "          <sum1:ImporteTotal>" . $total . "</sum1:ImporteTotal>\n";

        // Encadenamiento
        $xml .= "          <sum1:Encadenamiento>\n";
        if ($prevHash === '') {
            $xml .= "            <sum1:PrimerRegistro>S</sum1:PrimerRegistro>\n";
        } else {
            $xml .= "            <sum1:RegistroAnterior>\n";
            $xml .= "              <sum1:IDEmisorFactura>" . $nif . "</sum1:IDEmisorFactura>\n";
            $xml .= "              <sum1:NumSerieFactura>" . $numSerieSafe . "</sum1:NumSerieFactura>\n";
            $xml .= "              <sum1:FechaExpedicionFactura>" . $fechaExp . "</sum1:FechaExpedicionFactura>\n";
            $xml .= "              <sum1:Huella>" . $prevHash . "</sum1:Huella>\n";
            $xml .= "            </sum1:RegistroAnterior>\n";
        }
        $xml .= "          </sum1:Encadenamiento>\n";

        // SistemaInformatico (SistemaInformaticoType - all fields mandatory in XSD)
        $xml .= "          <sum1:SistemaInformatico>\n";
        $xml .= "            <sum1:NombreRazon>" . $nombre . "</sum1:NombreRazon>\n";
        $xml .= "            <sum1:NIF>" . $nif . "</sum1:NIF>\n";
        $xml .= "            <sum1:NombreSistemaInformatico>TPV</sum1:NombreSistemaInformatico>\n";
        $xml .= "            <sum1:IdSistemaInformatico>01</sum1:IdSistemaInformatico>\n";
        $xml .= "            <sum1:Version>1.0</sum1:Version>\n";
        $xml .= "            <sum1:NumeroInstalacion>01</sum1:NumeroInstalacion>\n";
        $xml .= "            <sum1:TipoUsoPosibleSoloVerifactu>S</sum1:TipoUsoPosibleSoloVerifactu>\n";
        $xml .= "            <sum1:TipoUsoPosibleMultiOT>N</sum1:TipoUsoPosibleMultiOT>\n";
        $xml .= "            <sum1:IndicadorMultiplesOT>N</sum1:IndicadorMultiplesOT>\n";
        $xml .= "          </sum1:SistemaInformatico>\n";

        // FechaHoraHusoGenRegistro
        $xml .= "          <sum1:FechaHoraHusoGenRegistro>" . $fechaHito . "</sum1:FechaHoraHusoGenRegistro>\n";

        // TipoHuella + Huella
        $xml .= "          <sum1:TipoHuella>01</sum1:TipoHuella>\n";
        $xml .= "          <sum1:Huella>" . $huella . "</sum1:Huella>\n";

        $xml .= "        </sum1:RegistroAlta>\n";
        $xml .= "      </sum:RegistroFactura>\n";
        $xml .= "    </sum:RegFactuSistemaFacturacion>";
        
        return $xml;
    }

    /**
     * Genera XML de Anulación según XSD oficial:
     * MISMO root RegFactuSistemaFacturacion > RegistroFactura > RegistroAnulacion (choice)
     *
     * RegistroFacturacionAnulacionType sequence (XSD):
     *   IDVersion, IDFactura{IDEmisorFacturaAnulada, NumSerieFacturaAnulada, FechaExpedicionFacturaAnulada},
     *   Encadenamiento, SistemaInformatico, FechaHoraHusoGenRegistro, TipoHuella, Huella
     */
    public static function generarXMLAnulacion($venta)
    {
        $nombre = htmlspecialchars(self::getConfig('TPV_RAZON_SOCIAL'), ENT_XML1, 'UTF-8');
        $nif = htmlspecialchars(self::getConfig('TPV_NIF'), ENT_XML1, 'UTF-8');
        $fechaExp = date('d-m-Y', strtotime($venta->getFecha()));
        $numero = htmlspecialchars($venta->getNumero(), ENT_XML1, 'UTF-8');
        $serie = $venta->getSerie() ? htmlspecialchars($venta->getSerie(), ENT_XML1, 'UTF-8') : '';
        $numSerieSafe = preg_replace('/\s+/', '', $serie . $numero);
        $fechaHito = date('Y-m-d\TH:i:sP');
        $prevHash = ($venta->getHashPrevio() && $venta->getHashPrevio() !== 'INITIAL_HASH_PLACEHOLDER') ? $venta->getHashPrevio() : '';
        $huella = self::calcularHuellaAnulacion($nif, $numSerieSafe, $fechaExp, $prevHash, $fechaHito);

        // --- Root: MISMO RegFactuSistemaFacturacion (XSD solo tiene este root) ---
        $xml = "    <sum:RegFactuSistemaFacturacion\n";
        $xml .= "        xmlns:sum=\"https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroLR.xsd\"\n";
        $xml .= "        xmlns:sum1=\"https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd\">\n";

        // --- Cabecera ---
        $xml .= "      <sum:Cabecera>\n";
        $xml .= "        <sum1:ObligadoEmision>\n";
        $xml .= "          <sum1:NombreRazon>" . $nombre . "</sum1:NombreRazon>\n";
        $xml .= "          <sum1:NIF>" . $nif . "</sum1:NIF>\n";
        $xml .= "        </sum1:ObligadoEmision>\n";
        $xml .= "      </sum:Cabecera>\n";

        // --- RegistroFactura > RegistroAnulacion (choice in XSD) ---
        $xml .= "      <sum:RegistroFactura>\n";
        $xml .= "        <sum1:RegistroAnulacion>\n";

        // IDVersion
        $xml .= "          <sum1:IDVersion>1.0</sum1:IDVersion>\n";

        // IDFactura (IDFacturaExpedidaBajaType: IDEmisorFacturaAnulada=NIF, NumSerieFacturaAnulada, FechaExpedicionFacturaAnulada)
        $xml .= "          <sum1:IDFactura>\n";
        $xml .= "            <sum1:IDEmisorFacturaAnulada>" . $nif . "</sum1:IDEmisorFacturaAnulada>\n";
        $xml .= "            <sum1:NumSerieFacturaAnulada>" . $numSerieSafe . "</sum1:NumSerieFacturaAnulada>\n";
        $xml .= "            <sum1:FechaExpedicionFacturaAnulada>" . $fechaExp . "</sum1:FechaExpedicionFacturaAnulada>\n";
        $xml .= "          </sum1:IDFactura>\n";

        // Encadenamiento
        $xml .= "          <sum1:Encadenamiento>\n";
        if ($prevHash === '') {
            $xml .= "            <sum1:PrimerRegistro>S</sum1:PrimerRegistro>\n";
        } else {
            $xml .= "            <sum1:RegistroAnterior>\n";
            $xml .= "              <sum1:IDEmisorFactura>" . $nif . "</sum1:IDEmisorFactura>\n";
            $xml .= "              <sum1:NumSerieFactura>" . $numSerieSafe . "</sum1:NumSerieFactura>\n";
            $xml .= "              <sum1:FechaExpedicionFactura>" . $fechaExp . "</sum1:FechaExpedicionFactura>\n";
            $xml .= "              <sum1:Huella>" . $prevHash . "</sum1:Huella>\n";
            $xml .= "            </sum1:RegistroAnterior>\n";
        }
        $xml .= "          </sum1:Encadenamiento>\n";

        // SistemaInformatico
        $xml .= "          <sum1:SistemaInformatico>\n";
        $xml .= "            <sum1:NombreRazon>" . $nombre . "</sum1:NombreRazon>\n";
        $xml .= "            <sum1:NIF>" . $nif . "</sum1:NIF>\n";
        $xml .= "            <sum1:NombreSistemaInformatico>TPV</sum1:NombreSistemaInformatico>\n";
        $xml .= "            <sum1:IdSistemaInformatico>01</sum1:IdSistemaInformatico>\n";
        $xml .= "            <sum1:Version>1.0</sum1:Version>\n";
        $xml .= "            <sum1:NumeroInstalacion>01</sum1:NumeroInstalacion>\n";
        $xml .= "            <sum1:TipoUsoPosibleSoloVerifactu>S</sum1:TipoUsoPosibleSoloVerifactu>\n";
        $xml .= "            <sum1:TipoUsoPosibleMultiOT>N</sum1:TipoUsoPosibleMultiOT>\n";
        $xml .= "            <sum1:IndicadorMultiplesOT>N</sum1:IndicadorMultiplesOT>\n";
        $xml .= "          </sum1:SistemaInformatico>\n";

        // FechaHoraHusoGenRegistro (XSD uses this name for BOTH alta and anulacion)
        $xml .= "          <sum1:FechaHoraHusoGenRegistro>" . $fechaHito . "</sum1:FechaHoraHusoGenRegistro>\n";

        // TipoHuella + Huella
        $xml .= "          <sum1:TipoHuella>01</sum1:TipoHuella>\n";
        $xml .= "          <sum1:Huella>" . $huella . "</sum1:Huella>\n";

        $xml .= "        </sum1:RegistroAnulacion>\n";
        $xml .= "      </sum:RegistroFactura>\n";
        $xml .= "    </sum:RegFactuSistemaFacturacion>";
        return $xml;
    }

    public static function enviarAEAT($xml)
    {
        $certPath = self::getConfig('CERT_PATH');
        $certPass = self::getConfig('CERT_PASS');
        $aeatUrl = self::getConfig('AEAT_URL_VERIFACTU');
        
        // Anulacion goes to different endpoint
        if (strpos($xml, 'RegistroAnulacion') !== false && strpos($xml, 'RegistroAlta') === false) {
            $aeatUrl = self::getAnulacionUrl();
        }

        if (!file_exists($certPath)) return ['success' => false, 'message' => 'Certificado no encontrado.'];
        $pfx = file_get_contents($certPath); $certs = [];
        if (!openssl_pkcs12_read($pfx, $certs, $certPass)) return ['success' => false, 'message' => 'Error certificado.'];

        $tempCert = tempnam(sys_get_temp_dir(), 'cert');
        $tempKey = tempnam(sys_get_temp_dir(), 'key');
        file_put_contents($tempCert, $certs['cert']);
        file_put_contents($tempKey, $certs['pkey']);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $aeatUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::prepararCuerpoSOAP($xml));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: ""'
        ]);
        curl_setopt($ch, CURLOPT_SSLCERT, $tempCert);
        curl_setopt($ch, CURLOPT_SSLKEY, $tempKey);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        @unlink($tempCert); @unlink($tempKey);

        if ($response === false) return ['success' => false, 'message' => 'Error CURL: ' . $curlError];

        // Intentar parsear con simplexml (puede fallar con SOAP namespaces)
        $respXml = @simplexml_load_string($response);
        if ($respXml) {
            $estado = (string)($respXml->xpath('//*[local-name()="EstadoRegistro"]')[0] ?? '');
            $estadoEnvio = (string)($respXml->xpath('//*[local-name()="EstadoEnvio"]')[0] ?? '');
            $csv = (string)($respXml->xpath('//*[local-name()="CSV"]')[0] ?? '');
            
            if ($estado === 'Correcto' || $estado === 'AceptadoConErrores' || $estadoEnvio === 'Correcto' || $estadoEnvio === 'ParcialmenteCorrecto') {
                return ['success' => true, 'csv' => $csv ?: ('REC' . rand(1000, 9999)), 'message' => 'OK'];
            }
            $errorDesc = (string)($respXml->xpath('//*[local-name()="DescripcionErrorRegistro"]')[0] ?? '');
            $fault = (string)($respXml->xpath('//*[local-name()="faultstring"]')[0] ?? 'Error AEAT');
            return ['success' => false, 'message' => $errorDesc ?: $fault, 'raw' => $response];
        }
        
        // Fallback regex: SOAP namespaces can break simplexml
        // Extraer EstadoRegistro y EstadoEnvio por regex
        $estado = '';
        $estadoEnvio = '';
        $csv = '';
        if (preg_match('/EstadoRegistro[^>]*>([^<]+)</s', $response, $m)) $estado = trim($m[1]);
        if (preg_match('/EstadoEnvio[^>]*>([^<]+)</s', $response, $m)) $estadoEnvio = trim($m[1]);
        if (preg_match('/CSV[^>]*>([^<]+)</s', $response, $m)) $csv = trim($m[1]);
        
        if ($estado === 'Correcto' || $estado === 'AceptadoConErrores' || $estadoEnvio === 'Correcto' || $estadoEnvio === 'ParcialmenteCorrecto') {
            return ['success' => true, 'csv' => $csv ?: ('REC' . rand(1000, 9999)), 'message' => 'OK'];
        }
        
        if (preg_match('/faultstring[^>]*>(.*?)<\//s', $response, $matches)) {
            return ['success' => false, 'message' => htmlspecialchars_decode($matches[1]), 'raw' => $response];
        }
        
        // Extraer error específico
        $errorDesc = '';
        if (preg_match('/DescripcionErrorRegistro[^>]*>([^<]+)</s', $response, $m)) $errorDesc = trim($m[1]);
        if ($errorDesc) {
            return ['success' => false, 'message' => $errorDesc, 'raw' => $response];
        }
        
        return ['success' => false, 'message' => 'Error respuesta (HTTP '.$httpCode.'): ' . substr(strip_tags($response), 0, 150), 'raw' => $response];
    }

    private static function prepararCuerpoSOAP($xml)
    {
        $xmlBody = trim(preg_replace('/<\?xml.*?\?>\s*/is', '', $xml));
        $soap = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $soap .= "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\"\n";
        $soap .= "    xmlns:sum=\"https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroLR.xsd\"\n";
        $soap .= "    xmlns:sum1=\"https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd\">\n";
        $soap .= "  <soapenv:Header/><soapenv:Body>\n    " . $xmlBody . "\n  </soapenv:Body>\n</soapenv:Envelope>";
        return $soap;
    }

    public static function generarURLQR($venta)
    {
        $nif = self::getConfig('TPV_NIF');
        $serie = $venta->getSerie() ?: '';
        $numero = $venta->getNumero() ?: '';
        // Must match NumSerieFactura in XML exactly (serie + numero, no padding)
        $numSerie = preg_replace('/\s+/', '', $serie . $numero);
        $params = [
            'nif' => $nif,
            'numserie' => $numSerie,
            'fecha' => date('d-m-Y', strtotime($venta->getFecha())),
            'importe' => number_format($venta->getTotal(), 2, '.', '')
        ];
        return self::getQRBaseUrl() . '?' . http_build_query($params);
    }
}