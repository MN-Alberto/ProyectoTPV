<?php

/**
 * Clase para la gestión de Verifactu (S.I.F.)
 * Implementa encadenamiento, generación de XML y envío a AEAT.
 * Estructura XML alineada estrictamente con el esquema XSD de la AEAT.
 */
require_once(__DIR__ . '/../model/Venta.php');

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
            } catch (Exception $e) {
            }
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
        if (isset(self::$config[$dbKey]))
            return self::$config[$dbKey];
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
        // Orden AEAT con etiquetas y ampersands: IDEmisorFactura=...&NumSerieFactura=...
        $str = "IDEmisorFactura=" . $nif .
            "&NumSerieFactura=" . $numSerie .
            "&FechaExpedicionFactura=" . $fechaExp .
            "&TipoFactura=" . $tipo .
            "&CuotaTotal=" . $cuota .
            "&ImporteTotal=" . $importe .
            "&Huella=" . ($prevHash ?: '') .
            "&FechaHoraHusoGenRegistro=" . $fechaHito;

        return strtoupper(hash('sha256', $str));
    }

    public static function calcularHuellaAnulacion($nif, $numSerie, $fechaExp, $prevHash, $fechaHito)
    {
        // Orden AEAT Anulación con etiquetas y ampersands
        $str = "IDEmisorFactura=" . $nif .
            "&NumSerieFactura=" . $numSerie .
            "&FechaExpedicionFactura=" . $fechaExp .
            "&Huella=" . ($prevHash ?: '') .
            "&FechaHoraHusoGenRegistro=" . $fechaHito;

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
     *   IDVersion, IDFactura, NombreRazonEmisor, TipoFactura,
     *   TipoRectificativa?, FacturasRectificadas?, DescripcionOperacion,
     *   Destinatarios?, Desglose, CuotaTotal, ImporteTotal,
     *   Encadenamiento, SistemaInformatico, FechaHoraHusoGenRegistro, TipoHuella, Huella
     *
     * @param Venta $venta La venta a registrar
     * @param array $lineas Líneas de venta (objetos o arrays)
     * @param array|null $datosRectificativa Datos del documento original si es rectificativa:
     *   ['serie' => 'T', 'numero' => '00001', 'fecha' => '2026-04-22', 'tipoOriginal' => 'F2']
     */
    public static function generarXML($venta, $lineas, $datosRectificativa = null)
    {
        $nombre = htmlspecialchars(self::getConfig('TPV_RAZON_SOCIAL'), ENT_XML1, 'UTF-8');
        $nif = htmlspecialchars(self::getConfig('TPV_NIF'), ENT_XML1, 'UTF-8');
        // ✅ FIX AEAT: ALTAS usan DD-MM-YYYY
        $fechaTs = strtotime($venta->getFecha());
        $fechaExp = date('d-m-Y', $fechaTs);
        $numero = htmlspecialchars($venta->getNumero(), ENT_XML1, 'UTF-8');
        $serie = $venta->getSerie() ? htmlspecialchars($venta->getSerie(), ENT_XML1, 'UTF-8') : '';
        $numSerieSafe = preg_replace('/\s+/', '', $serie . $numero);
        $total = number_format($venta->getTotal(), 2, '.', '');
        $fechaHito = date('Y-m-d\TH:i:sP');

        // Determinar TipoFactura: R1/R5 si es rectificativa, F1/F2 si es alta normal
        if ($datosRectificativa) {
            $tipoOriginal = $datosRectificativa['tipoOriginal'] ?? 'F2';
            $tipoFactura = ($tipoOriginal === 'F1') ? 'R1' : 'R5';
        } else {
            $tipoFactura = $venta->getClienteDni() ? 'F1' : 'F2';
        }

        $desgloseIVA = [];
        $cuotaTotalTax = 0;
        foreach ($lineas as $linea) {
            // Support both LineaVenta objects and assoc arrays from fetchAll
            $rate = is_object($linea) ? (float) ($linea->getIva() ?? 21) : (float) ($linea['iva'] ?? 21);
            $base = is_object($linea) ? (float) $linea->getSubtotal() : (float) ($linea['subtotal'] ?? 0);
            if (!isset($desgloseIVA["$rate"]))
                $desgloseIVA["$rate"] = ['base' => 0, 'cuota' => 0];
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

        // --- Bloques exclusivos de rectificativas (XSD sequence: TipoRectificativa, FacturasRectificadas) ---
        if ($datosRectificativa) {
            // TipoRectificativa: I = Incremental (por diferencias, importes negativos)
            $xml .= "          <sum1:TipoRectificativa>I</sum1:TipoRectificativa>\n";

            // FacturasRectificadas > IDFacturaRectificada (IDFacturaARType)
            $origSerie = htmlspecialchars($datosRectificativa['serie'] ?? '', ENT_XML1, 'UTF-8');
            $origNumero = htmlspecialchars($datosRectificativa['numero'] ?? '', ENT_XML1, 'UTF-8');
            $origNumSerie = preg_replace('/\s+/', '', $origSerie . $origNumero);
            $origFecha = date('d-m-Y', strtotime($datosRectificativa['fecha']));

            $xml .= "          <sum1:FacturasRectificadas>\n";
            $xml .= "            <sum1:IDFacturaRectificada>\n";
            $xml .= "              <sum1:IDEmisorFactura>" . $nif . "</sum1:IDEmisorFactura>\n";
            $xml .= "              <sum1:NumSerieFactura>" . $origNumSerie . "</sum1:NumSerieFactura>\n";
            $xml .= "              <sum1:FechaExpedicionFactura>" . $origFecha . "</sum1:FechaExpedicionFactura>\n";
            $xml .= "            </sum1:IDFacturaRectificada>\n";
            $xml .= "          </sum1:FacturasRectificadas>\n";
        }

        // DescripcionOperacion (OBLIGATORIO en XSD)
        $descripcion = $datosRectificativa
            ? 'Rectificacion de ' . preg_replace('/\s+/', '', ($datosRectificativa['serie'] ?? '') . ($datosRectificativa['numero'] ?? ''))
            : 'Venta';
        $xml .= "          <sum1:DescripcionOperacion>" . htmlspecialchars($descripcion, ENT_XML1, 'UTF-8') . "</sum1:DescripcionOperacion>\n";

        // Destinatarios (OBLIGATORIO para F1, R1, R2, R3, R4 según AEAT)
        if (in_array($tipoFactura, ['F1', 'R1', 'R2', 'R3', 'R4']) && $venta->getClienteDni()) {
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
            $xml .= "              <sum1:TipoImpositivo>" . number_format((float) $rate, 2, '.', '') . "</sum1:TipoImpositivo>\n";
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
            // ✅ Verifactu: Usar datos del registro anterior real (si existen) para el encadenamiento
            $origNif = $nif;
            $origNumSerie = $numSerieSafe;
            $origFecha = $fechaExp;

            if (isset($venta->datosRegistroAnterior) && !empty($venta->datosRegistroAnterior)) {
                $origNumSerie = preg_replace('/\s+/', '', ($venta->datosRegistroAnterior['serie'] ?? '') . ($venta->datosRegistroAnterior['numero'] ?? ''));
                $origFecha = date('d-m-Y', strtotime($venta->datosRegistroAnterior['fecha']));
            } else {
                // Si no vienen los datos, intentar buscarlos por el hash previo en la BD
                $conexion = ConexionDB::getInstancia()->getConexion();
                $sqlPrev = "
                    SELECT v.serie, v.numero, v.fecha 
                    FROM (
                        SELECT vi.serie, vi.numero, t.fecha, t.hash FROM tickets t JOIN ventas_ids vi ON t.id = vi.id
                        UNION ALL
                        SELECT vi.serie, vi.numero, f.fecha, f.hash FROM facturas f JOIN ventas_ids vi ON f.id = vi.id
                    ) v
                    WHERE v.hash = ? LIMIT 1";
                $stmtPrev = $conexion->prepare($sqlPrev);
                $stmtPrev->execute([$prevHash]);
                $rowPrev = $stmtPrev->fetch(PDO::FETCH_ASSOC);
                if ($rowPrev) {
                    $origNumSerie = preg_replace('/\s+/', '', ($rowPrev['serie'] ?? '') . ($rowPrev['numero'] ?? ''));
                    $origFecha = date('d-m-Y', strtotime($rowPrev['fecha']));
                }
            }

            $xml .= "            <sum1:RegistroAnterior>\n";
            $xml .= "              <sum1:IDEmisorFactura>" . $origNif . "</sum1:IDEmisorFactura>\n";
            $xml .= "              <sum1:NumSerieFactura>" . $origNumSerie . "</sum1:NumSerieFactura>\n";
            $xml .= "              <sum1:FechaExpedicionFactura>" . $origFecha . "</sum1:FechaExpedicionFactura>\n";
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

        return ['xml' => $xml, 'huella' => $huella];
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
        // ✅ FIX AEAT: FechaExpedicionFactura debe ser DD-MM-YYYY (Tipo FechaType en XSD)
        $fechaTs = strtotime($venta->getFecha());
        $fechaExp = date('d-m-Y', $fechaTs);
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
            // ✅ Verifactu: Usar datos del registro anterior real (si existen) para el encadenamiento
            $origNif = $nif;
            $origNumSerie = $numSerieSafe;
            $origFecha = $fechaExp;

            if (isset($venta->datosRegistroAnterior) && !empty($venta->datosRegistroAnterior)) {
                $origNumSerie = preg_replace('/\s+/', '', ($venta->datosRegistroAnterior['serie'] ?? '') . ($venta->datosRegistroAnterior['numero'] ?? ''));
                $origFecha = date('d-m-Y', strtotime($venta->datosRegistroAnterior['fecha']));
            }

            $xml .= "            <sum1:RegistroAnterior>\n";
            $xml .= "              <sum1:IDEmisorFactura>" . $origNif . "</sum1:IDEmisorFactura>\n";
            $xml .= "              <sum1:NumSerieFactura>" . $origNumSerie . "</sum1:NumSerieFactura>\n";
            $xml .= "              <sum1:FechaExpedicionFactura>" . $origFecha . "</sum1:FechaExpedicionFactura>\n";
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

        return ['xml' => $xml, 'huella' => $huella];
    }

    public static function enviarAEATLote($xmlConsolidado)
    {
        $certPath = self::getConfig('CERT_PATH');
        $certPass = self::getConfig('CERT_PASS');
        $aeatUrl = self::getConfig('AEAT_URL_VERIFACTU');

        $erroresConexion = [CURLE_COULDNT_CONNECT, CURLE_COULDNT_RESOLVE_HOST, CURLE_COULDNT_RESOLVE_PROXY, CURLE_OPERATION_TIMEOUTED, CURLE_GOT_NOTHING, CURLE_SEND_ERROR, CURLE_RECV_ERROR];

        if (!file_exists($certPath))
            return ['success' => false, 'message' => 'Certificado no encontrado.'];
        $pfx = file_get_contents($certPath);
        $certs = [];
        if (!openssl_pkcs12_read($pfx, $certs, $certPass))
            return ['success' => false, 'message' => 'Error certificado.'];

        $tempCert = tempnam(sys_get_temp_dir(), 'cert');
        $tempKey = tempnam(sys_get_temp_dir(), 'key');
        file_put_contents($tempCert, $certs['cert']);
        file_put_contents($tempKey, $certs['pkey']);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $aeatUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::prepararCuerpoSOAP($xmlConsolidado));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml; charset=utf-8', 'SOAPAction: ""']);
        curl_setopt($ch, CURLOPT_SSLCERT, $tempCert);
        curl_setopt($ch, CURLOPT_SSLKEY, $tempKey);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        @unlink($tempCert);
        @unlink($tempKey);

        if ($response === false) {
            return ['success' => false, 'message' => 'Error CURL: ' . $curlError, 'es_error_conexion' => in_array($curlErrno, $erroresConexion)];
        }

        $respXml = @simplexml_load_string($response);
        $resultados = [];
        $tiempoEspera = 0;

        if ($respXml) {
            $tiempoEsperaStr = (string) ($respXml->xpath('//*[local-name()="TiempoEsperaEnvio"]')[0] ?? '');
            if ($tiempoEsperaStr !== '') {
                $tiempoEspera = (int) $tiempoEsperaStr;
            }

            // Extraer resultados por línea
            $lineas = $respXml->xpath('//*[local-name()="RespuestaLinea"]');
            foreach ($lineas as $linea) {
                $idFactura = $linea->xpath('.//*[local-name()="IDFactura"]')[0] ?? null;
                $numSerie = '';
                if ($idFactura) {
                    $numSerie = (string) ($idFactura->xpath('.//*[local-name()="NumSerieFactura"]')[0] ?? (string) ($idFactura->xpath('.//*[local-name()="NumSerieFacturaAnulada"]')[0] ?? ''));
                }
                $estado = (string) ($linea->xpath('.//*[local-name()="EstadoRegistro"]')[0] ?? '');
                $csv = (string) ($linea->xpath('.//*[local-name()="CSV"]')[0] ?? '');
                $errCode = (string) ($linea->xpath('.//*[local-name()="CodigoErrorRegistro"]')[0] ?? '');
                $errDesc = (string) ($linea->xpath('.//*[local-name()="DescripcionErrorRegistro"]')[0] ?? '');

                $resultados[] = [
                    'num_serie' => $numSerie,
                    'success' => ($estado === 'Correcto' || $estado === 'AceptadoConErrores'),
                    'csv' => $csv,
                    'codigo_error' => $errCode,
                    'message' => $errDesc ?: ($estado === 'Correcto' ? 'OK' : 'Error AEAT')
                ];
            }
        } else {
            // FALLBACK REGEX para lotes
            error_log("DEBUG Verifactu Lote: Fallback Regex. HTTP $httpCode");

            // Intentar extraer TiempoEsperaEnvio
            if (preg_match('/TiempoEsperaEnvio[^>]*>([^<]+)</s', $response, $m)) {
                $tiempoEspera = (int) trim($m[1]);
            }

            // Intentar extraer bloques RespuestaLinea
            if (preg_match_all('/<[^:]*:RespuestaLinea>(.*?)<\/[^:]*:RespuestaLinea>/is', $response, $matches)) {
                foreach ($matches[1] as $inner) {
                    $numSerie = '';
                    if (preg_match('/NumSerieFactura[^>]*>([^<]+)</s', $inner, $m))
                        $numSerie = trim($m[1]);
                    else if (preg_match('/NumSerieFacturaAnulada[^>]*>([^<]+)</s', $inner, $m))
                        $numSerie = trim($m[1]);

                    $estado = '';
                    if (preg_match('/EstadoRegistro[^>]*>([^<]+)</s', $inner, $m))
                        $estado = trim($m[1]);

                    $csv = '';
                    if (preg_match('/CSV[^>]*>([^<]+)</s', $inner, $m))
                        $csv = trim($m[1]);

                    $errCode = '';
                    if (preg_match('/CodigoErrorRegistro[^>]*>([^<]+)</s', $inner, $m))
                        $errCode = trim($m[1]);

                    $errDesc = '';
                    if (preg_match('/DescripcionErrorRegistro[^>]*>([^<]+)</s', $inner, $m))
                        $errDesc = trim($m[1]);

                    $resultados[] = [
                        'num_serie' => $numSerie,
                        'success' => ($estado === 'Correcto' || $estado === 'AceptadoConErrores'),
                        'csv' => $csv,
                        'codigo_error' => $errCode,
                        'message' => $errDesc ?: ($estado === 'Correcto' ? 'OK' : 'Error AEAT')
                    ];
                }
            }

            if (empty($resultados)) {
                return ['success' => false, 'message' => "Error crítico: No se pudo parsear respuesta de lote (HTTP $httpCode).", 'es_error_conexion' => false];
            }
        }

        // Solo guardar cooldown si hubo al menos un éxito (según requerimiento de usuario)
        if ($tiempoEspera > 0) {
            $hayExito = false;
            foreach ($resultados as $res) {
                if ($res['success']) {
                    $hayExito = true;
                    break;
                }
            }
            if ($hayExito) {
                self::guardarCooldownAEAT($tiempoEspera);
            } else {
                error_log("DEBUG Verifactu Lote: Ignorando cooldown AEAT ({$tiempoEspera}s) porque todos los registros fallaron.");
                $tiempoEspera = 0; // Para que no se devuelva como cooldown activo
            }
        }

        return ['success' => true, 'resultados' => $resultados, 'tiempo_espera' => $tiempoEspera, 'raw_response' => $response];
    }

    public static function enviarAEAT($xml)
    {
        $certPath = self::getConfig('CERT_PATH');
        $certPass = self::getConfig('CERT_PASS');
        $aeatUrl = self::getConfig('AEAT_URL_VERIFACTU');

        // Errores CURL que indican fallo de conexión (no internet / servidor caído)
        $erroresConexion = [
            CURLE_COULDNT_CONNECT,
            CURLE_COULDNT_RESOLVE_HOST,
            CURLE_COULDNT_RESOLVE_PROXY,
            CURLE_OPERATION_TIMEOUTED,
            CURLE_GOT_NOTHING,
            CURLE_SEND_ERROR,
            CURLE_RECV_ERROR,
        ];

        $maxIntentos = 1; // Ya existe un sistema de cola en segundo plano, no bloquear el TPV reintentando aquí.
        $ultimoError = '';
        $esErrorConexion = false;
        $codigoError = null;

        for ($intento = 1; $intento <= $maxIntentos; $intento++) {

            if (!file_exists($certPath))
                return ['success' => false, 'message' => 'Certificado no encontrado.', 'es_error_conexion' => false, 'codigo_error' => 'CERT_NOT_FOUND'];
            $pfx = file_get_contents($certPath);
            $certs = [];
            if (!openssl_pkcs12_read($pfx, $certs, $certPass))
                return ['success' => false, 'message' => 'Error certificado.', 'es_error_conexion' => false, 'codigo_error' => 'CERT_INVALID'];

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
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Timeout de conexión bajo
            curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Timeout total bajo, si tarda va a la cola

            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            @unlink($tempCert);
            @unlink($tempKey);

            if ($response === false) {
                $ultimoError = 'Error CURL: ' . $curlError;
                $esErrorConexion = in_array($curlErrno, $erroresConexion);
                $codigoError = 'CURL_' . $curlErrno;
            } else {
                $esErrorConexion = false;
                // Intentar parsear con simplexml
                $respXml = @simplexml_load_string($response);
                if ($respXml) {
                    $estado = (string) ($respXml->xpath('//*[local-name()="EstadoRegistro"]')[0] ?? '');
                    $estadoEnvio = (string) ($respXml->xpath('//*[local-name()="EstadoEnvio"]')[0] ?? '');
                    $csv = (string) ($respXml->xpath('//*[local-name()="CSV"]')[0] ?? '');
                    $codigoError = (string) ($respXml->xpath('//*[local-name()="CodigoErrorRegistro"]')[0] ?? null);
                    $tiempoEspera = (string) ($respXml->xpath('//*[local-name()="TiempoEsperaEnvio"]')[0] ?? '');

                    // Solo guardar cooldown si es éxito (según requerimiento de usuario)
                    $esExito = ($estado === 'Correcto' || $estado === 'AceptadoConErrores' || $estadoEnvio === 'Correcto' || $estadoEnvio === 'ParcialmenteCorrecto');
                    if ($tiempoEspera !== '' && $esExito) {
                        self::guardarCooldownAEAT((int) $tiempoEspera);
                    }

                    if ($estado === 'Correcto' || $estado === 'AceptadoConErrores' || $estadoEnvio === 'Correcto' || $estadoEnvio === 'ParcialmenteCorrecto') {
                        return [
                            'success' => true,
                            'csv' => $csv ?: ('REC' . rand(1000, 9999)),
                            'message' => 'OK',
                            'es_error_conexion' => false,
                            'codigo_error' => null,
                            'tiempo_espera' => (int) $tiempoEspera,
                            'raw_response' => $response
                        ];
                    }
                    $errorDesc = (string) ($respXml->xpath('//*[local-name()="DescripcionErrorRegistro"]')[0] ?? '');
                    $fault = (string) ($respXml->xpath('//*[local-name()="faultstring"]')[0] ?? 'Error AEAT');
                    $ultimoError = $errorDesc ?: $fault;
                } else {
                    // Fallback regex
                    $estado = '';
                    $estadoEnvio = '';
                    $csv = '';
                    if (preg_match('/EstadoRegistro[^>]*>([^<]+)</s', $response, $m))
                        $estado = trim($m[1]);
                    if (preg_match('/EstadoEnvio[^>]*>([^<]+)</s', $response, $m))
                        $estadoEnvio = trim($m[1]);
                    if (preg_match('/CSV[^>]*>([^<]+)</s', $response, $m))
                        $csv = trim($m[1]);
                    if (preg_match('/CodigoErrorRegistro[^>]*>([^<]+)</s', $response, $m))
                        $codigoError = trim($m[1]);
                    $tiempoEspera = 0;
                    if (preg_match('/TiempoEsperaEnvio[^>]*>([^<]+)</s', $response, $m))
                        $tiempoEspera = (int) trim($m[1]);
                    if ($tiempoEspera > 0) {
                        // Solo guardar cooldown si es éxito (según requerimiento de usuario)
                        $esExito = ($estado === 'Correcto' || $estado === 'AceptadoConErrores' || $estadoEnvio === 'Correcto' || $estadoEnvio === 'ParcialmenteCorrecto');
                        if ($esExito) {
                            self::guardarCooldownAEAT($tiempoEspera);
                        } else {
                            error_log("DEBUG Verifactu: Ignorando cooldown AEAT ({$tiempoEspera}s) porque el registro falló.");
                            $tiempoEspera = 0;
                        }
                    }

                    if ($estado === 'Correcto' || $estado === 'AceptadoConErrores' || $estadoEnvio === 'Correcto' || $estadoEnvio === 'ParcialmenteCorrecto') {
                        return [
                            'success' => true,
                            'csv' => $csv ?: ('REC' . rand(1000, 9999)),
                            'message' => 'OK',
                            'es_error_conexion' => false,
                            'codigo_error' => null,
                            'tiempo_espera' => $tiempoEspera,
                            'raw_response' => $response
                        ];
                    }

                    if (preg_match('/faultstring[^>]*>(.*?)<\//s', $response, $matches)) {
                        $ultimoError = htmlspecialchars_decode($matches[1]);
                    } elseif (preg_match('/DescripcionErrorRegistro[^>]*>([^<]+)</s', $response, $m)) {
                        $ultimoError = trim($m[1]);
                    } else {
                        $ultimoError = 'Error respuesta (HTTP ' . $httpCode . '): ' . substr(strip_tags($response), 0, 150);
                    }
                }
            }

            // Si es error de conexión, no reintentar inmediatamente (será encolado)
            if ($esErrorConexion && $intento >= 2)
                break;

            if ($intento < $maxIntentos) {
                sleep(1);
                continue;
            }
        }

        return [
            'success' => false,
            'message' => $ultimoError,
            'es_error_conexion' => $esErrorConexion,
            'codigo_error' => $codigoError,
            'raw_response' => $response ?? null
        ];
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

    // ======================== VALIDACIÓN PRE-ENVÍO ========================

    /**
     * Valida NIF español (persona física o jurídica).
     * @return bool
     */
    public static function validarNIF($nif)
    {
        if (!$nif || strlen($nif) !== 9)
            return false;
        // Persona física: 12345678A
        if (preg_match('/^\d{8}[A-Z]$/i', $nif))
            return true;
        // CIF jurídica: A12345678 o A1234567B
        if (preg_match('/^[ABCDEFGHJKLMNPQRSUVW]\d{7}[0-9A-J]$/i', $nif))
            return true;
        // NIE: X/Y/Z + 7 dígitos + letra
        if (preg_match('/^[XYZ]\d{7}[A-Z]$/i', $nif))
            return true;
        return false;
    }

    /**
     * Comprueba coherencia NIF↔Nombre.
     * NIF jurídica (A-H,J,N,P-S,U,V,W) → nombre no debe estar vacío.
     * NIF persona física → nombre no debe parecer razón social.
     * @return array ['valid' => bool, 'warning' => string|null]
     */
    public static function validarNIFNombre($nif, $nombre)
    {
        if (!$nif || !$nombre) {
            return ['valid' => false, 'warning' => 'NIF y Nombre son obligatorios.'];
        }
        $primeraLetra = strtoupper(substr($nif, 0, 1));
        $letrasJuridica = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'N', 'P', 'Q', 'R', 'S', 'U', 'V', 'W'];
        $esJuridica = in_array($primeraLetra, $letrasJuridica);

        if ($esJuridica && strlen(trim($nombre)) < 3) {
            return ['valid' => false, 'warning' => 'NIF jurídico requiere razón social (nombre demasiado corto).'];
        }
        return ['valid' => true, 'warning' => null];
    }

    /**
     * Valida todos los campos obligatorios antes de generar XML.
     * @param Venta $venta
     * @param array $lineas
     * @return array ['valid' => bool, 'errors' => [...]]
     */
    public static function validarDatosPreEnvio($venta, $lineas)
    {
        $errors = [];
        $nif = self::getConfig('TPV_NIF');
        $nombre = self::getConfig('TPV_RAZON_SOCIAL');

        // VAL_001: NIF formato
        if (!self::validarNIF($nif)) {
            $errors[] = ['code' => 'VAL_001', 'field' => 'NIF', 'message' => "NIF inválido: '$nif'. Debe tener 9 caracteres con formato válido."];
        }

        // VAL_001b: NIF↔Nombre coherencia
        $nifNom = self::validarNIFNombre($nif, $nombre);
        if (!$nifNom['valid']) {
            $errors[] = ['code' => 'VAL_001b', 'field' => 'NIF/Nombre', 'message' => $nifNom['warning']];
        }

        // VAL_002: NumSerieFactura
        $serie = $venta->getSerie() ?: '';
        $numero = $venta->getNumero() ?: '';
        $numSerie = preg_replace('/\s+/', '', $serie . $numero);
        if (empty($numSerie)) {
            $errors[] = ['code' => 'VAL_002', 'field' => 'NumSerieFactura', 'message' => 'Número de serie/factura vacío.'];
        } elseif (strlen($numSerie) > 60) {
            $errors[] = ['code' => 'VAL_002', 'field' => 'NumSerieFactura', 'message' => 'NumSerieFactura excede 60 caracteres.'];
        }

        // VAL_003: Fecha
        $fecha = $venta->getFecha();
        if (!$fecha || !strtotime($fecha)) {
            $errors[] = ['code' => 'VAL_003', 'field' => 'Fecha', 'message' => 'Fecha de expedición inválida.'];
        } elseif (strtotime($fecha) > time() + 86400) {
            $errors[] = ['code' => 'VAL_003', 'field' => 'Fecha', 'message' => 'La fecha de expedición no puede ser futura.'];
        }

        // VAL_004: ImporteTotal
        $total = $venta->getTotal();
        if (!is_numeric($total)) {
            $errors[] = ['code' => 'VAL_004', 'field' => 'ImporteTotal', 'message' => 'Importe total no es numérico.'];
        }

        // VAL_005: Desglose IVA
        if (!$lineas || count($lineas) === 0) {
            $errors[] = ['code' => 'VAL_005', 'field' => 'Desglose', 'message' => 'No hay líneas de venta para el desglose IVA.'];
        }

        // VAL_006: Cuota coherente
        if ($lineas && count($lineas) > 0) {
            $cuotaCalc = 0;
            foreach ($lineas as $l) {
                $rate = is_object($l) ? (float) ($l->getIva() ?? 21) : (float) ($l['iva'] ?? 21);
                $base = is_object($l) ? (float) $l->getSubtotal() : (float) ($l['subtotal'] ?? 0);
                $cuotaCalc += $base * ($rate / 100);
            }
            $baseTotal = 0;
            foreach ($lineas as $l) {
                $baseTotal += is_object($l) ? (float) $l->getSubtotal() : (float) ($l['subtotal'] ?? 0);
            }
            $totalEsperado = round($baseTotal + $cuotaCalc, 2);
            if (abs($totalEsperado - (float) $total) > 0.05) {
                $errors[] = ['code' => 'VAL_006', 'field' => 'CuotaTotal', 'message' => "Cuota incoherente: esperado $totalEsperado, recibido $total (diferencia > 0.05€)."];
            }
        }

        // VAL_007: TipoFactura (se calcula en generarXML, validamos prerequisitos)
        $clienteDni = $venta->getClienteDni();
        if ($clienteDni && !self::validarNIF($clienteDni)) {
            $errors[] = ['code' => 'VAL_007', 'field' => 'ClienteNIF', 'message' => "NIF del destinatario inválido: '$clienteDni'."];
        }

        // VAL_008: Certificado
        $certPath = self::getConfig('CERT_PATH');
        if (!$certPath || !file_exists($certPath)) {
            $errors[] = ['code' => 'VAL_008', 'field' => 'Certificado', 'message' => 'Certificado digital no encontrado: ' . ($certPath ?: 'ruta vacía')];
        }

        // VAL_009: URL AEAT
        $url = self::getConfig('AEAT_URL_VERIFACTU');
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = ['code' => 'VAL_009', 'field' => 'URL_AEAT', 'message' => 'URL del endpoint AEAT inválida o vacía.'];
        }

        return ['valid' => count($errors) === 0, 'errors' => $errors];
    }

    // ======================== COLA DE ENVÍOS ========================

    /**
     * Encola un envío pendiente para reintento automático.
     */
    public static function encolarEnvio($idDocumento, $tablaOrigen, $tipoEnvio, $xml, $error = '', $codigoError = null, $esConexion = false, $numDocumento = null, $estado = 'pendiente', $respuestaXml = null, $csvAeat = null)
    {
        error_log("DEBUG Verifactu: Encolando envio para documento $numDocumento ($idDocumento $tablaOrigen)...");
        $pdo = ConexionDB::getInstancia()->getConexion();
        $intervalo = ($estado === 'pendiente') ? 0 : (int) (self::getConfig('verifactu_intervalo_reintento') ?: 15);

        $stmt = $pdo->prepare(
            "INSERT INTO verifactu_cola_envios 
             (id_documento, num_documento, tabla_origen, tipo_envio, xml_contenido, respuesta_xml, csv_aeat, estado, ultimo_error, codigo_error_aeat, es_error_conexion, proximo_reintento)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))"
        );
        $stmt->execute([$idDocumento, $numDocumento, $tablaOrigen, $tipoEnvio, $xml, $respuestaXml, $csvAeat, $estado, $error, $codigoError, $esConexion ? 1 : 0, $intervalo]);
        $idCola = $pdo->lastInsertId();
        error_log("DEBUG Verifactu: Encolado con exito. ID Cola: $idCola");
        return $idCola;
    }

    /**
     * Procesa la cola de envíos pendientes (llamado por timer cada X minutos).
     * @return array Resumen del procesamiento
     */
    public static function procesarColaPendientes($auto = false)
    {
        $pdo = ConexionDB::getInstancia()->getConexion();
        $maxLote = (int) (self::getConfig('verifactu_max_lote') ?: 100);

        // ✅ PROCESAR SOLO estado 'pendiente' y colgados 'enviando'
        // ❌ EXCLUYENDO COMPLETAMENTE error_temporal y error_permanente del procesamiento AUTOMATICO
        // Estos estados solo se reenvian MANUALMENTE por el usuario
        $stmt = $pdo->prepare(
            "SELECT * FROM verifactu_cola_envios 
             WHERE (estado = 'pendiente' OR (estado = 'enviando' AND fecha_ultimo_intento < DATE_SUB(NOW(), INTERVAL 5 MINUTE)))
                AND (proximo_reintento IS NULL OR proximo_reintento <= NOW())
                AND intentos < max_intentos
             ORDER BY fecha_creacion ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $maxLote, PDO::PARAM_INT);
        $stmt->execute();
        $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resumen = ['procesados' => 0, 'exitosos' => 0, 'fallidos' => 0, 'cooldown_segundos' => 0];

        if (empty($pendientes))
            return $resumen;

        // Respetar cooldown AEAT (TiempoEsperaEnvio)
        $cooldown = self::getCooldownRestante();
        if ($cooldown > 0) {
            $resumen['cooldown_segundos'] = $cooldown;
            return $resumen;
        }

        // --- CONSOLIDACIÓN DEL LOTE ---
        $xmlRegistros = "";
        $cabecera = "";
        $mapaIds = [];
        $necesitaIncidencia = false;
        $ahora = time();

        foreach ($pendientes as $envio) {
            $resumen['procesados']++;

            // --- VERIFICAR SI NECESITA INCIDENCIA (Error 2004) ---
            // Si algún registro es anterior a hace 4 minutos, marcaremos el lote con Incidencia=S
            if (!$necesitaIncidencia && preg_match('/<sum1:FechaHoraHusoGenRegistro>(.*?)<\/sum1:FechaHoraHusoGenRegistro>/is', $envio['xml_contenido'], $mTime)) {
                $xmlTime = strtotime($mTime[1]);
                if ($xmlTime < ($ahora - 240)) { // 4 minutos
                    $necesitaIncidencia = true;
                }
            }

            // Extraer Cabecera del primer registro
            if (empty($cabecera)) {
                if (preg_match('/<sum:Cabecera>(.*?)<\/sum:Cabecera>/is', $envio['xml_contenido'], $m)) {
                    $cabecera = $m[0];
                    // Inyectar Incidencia si es necesario
                    if ($necesitaIncidencia) {
                        $bloqueIncidencia = "<sum1:RemisionVoluntaria><sum1:Incidencia>S</sum1:Incidencia></sum1:RemisionVoluntaria>";
                        $cabecera = str_replace('</sum:Cabecera>', $bloqueIncidencia . '</sum:Cabecera>', $cabecera);
                    }
                }
            }
            // Extraer todos los RegistroFactura (puede haber uno o varios)
            if (preg_match_all('/<sum:RegistroFactura>(.*?)<\/sum:RegistroFactura>/is', $envio['xml_contenido'], $matches)) {
                foreach ($matches[0] as $reg) {
                    $xmlRegistros .= $reg . "\n";
                }
            }
            $key = $envio['num_documento'];
            $mapaIds[$key] = $envio;

            // Marcar como "enviando"
            $pdo->prepare("UPDATE verifactu_cola_envios SET estado = 'enviando', fecha_ultimo_intento = NOW() WHERE id = ?")
                ->execute([$envio['id']]);
        }

        // Construir XML consolidado
        $xmlConsolidado = "<sum:RegFactuSistemaFacturacion\n";
        $xmlConsolidado .= "    xmlns:sum=\"https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroLR.xsd\"\n";
        $xmlConsolidado .= "    xmlns:sum1=\"https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd\">\n";
        $xmlConsolidado .= "  " . $cabecera . "\n";
        $xmlConsolidado .= "  " . $xmlRegistros . "\n";
        $xmlConsolidado .= "</sum:RegFactuSistemaFacturacion>";

        // Enviar lote
        $resLote = self::enviarAEATLote($xmlConsolidado);
        $resumen['cooldown_segundos'] = $resLote['tiempo_espera'] ?? 0;

        if ($resLote['success'] && isset($resLote['resultados'])) {
            foreach ($resLote['resultados'] as $res) {
                $envio = $mapaIds[$res['num_serie']] ?? null;
                if (!$envio)
                    continue;

                $intentos = $envio['intentos'] + 1;
                $intervalo = (int) (self::getConfig('verifactu_intervalo_reintento') ?: 15);

                if ($res['success']) {
                    $resumen['exitosos']++;
                    $pdo->prepare("UPDATE verifactu_cola_envios SET estado = 'enviado', intentos = ?, fecha_envio_exitoso = NOW(), ultimo_error = NULL, csv_aeat = ?, respuesta_xml = ? WHERE id = ?")
                        ->execute([$intentos, $res['csv'] ?? null, $resLote['raw_response'] ?? null, $envio['id']]);
                    $pdo->prepare("UPDATE {$envio['tabla_origen']} SET estado_aeat = 'enviado', csv_aeat = ?, error_aeat = NULL WHERE id = ?")
                        ->execute([$res['csv'], $envio['id_documento']]);
                    self::registrarEvento('reintento_ok', $envio['id_documento'], $envio['tabla_origen'], "Reenvío en lote exitoso. CSV: " . $res['csv']);
                } else {
                    $resumen['fallidos']++;
                    $nuevoEstado = ($intentos >= $envio['max_intentos']) ? 'error_permanente' : 'error_temporal';
                    $pdo->prepare("UPDATE verifactu_cola_envios SET estado = ?, intentos = ?, ultimo_error = ?, codigo_error_aeat = ?, respuesta_xml = ?, proximo_reintento = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?")
                        ->execute([$nuevoEstado, $intentos, $res['message'], $res['codigo_error'], $resLote['raw_response'] ?? null, $intervalo, $envio['id']]);
                    self::registrarEvento('reintento_error', $envio['id_documento'], $envio['tabla_origen'], "Error en lote: " . $res['message']);
                }
            }
        } else {
            // Error general del lote (ej. conexión)
            $esConexion = $resLote['es_error_conexion'] ?? false;
            foreach ($pendientes as $envio) {
                $resumen['fallidos']++;
                $intentos = $envio['intentos'] + 1;
                $intervalo = (int) (self::getConfig('verifactu_intervalo_reintento') ?: 15);
                $nuevoEstado = ($intentos >= $envio['max_intentos'] || !$esConexion) ? 'error_permanente' : 'error_temporal';
                $pdo->prepare("UPDATE verifactu_cola_envios SET estado = ?, intentos = ?, ultimo_error = ?, es_error_conexion = ?, proximo_reintento = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?")
                    ->execute([$nuevoEstado, $intentos, $resLote['message'], $esConexion ? 1 : 0, $intervalo, $envio['id']]);
            }
        }

        if ($resumen['procesados'] > 0) {
            self::registrarEvento('cola_procesada_lote', null, null, "Cola procesada en lote: {$resumen['exitosos']}/{$resumen['procesados']} exitosos.");
        }

        return $resumen;
    }



    // ======================== LIBRO DE EVENTOS ========================

    /**
     * Registra un evento en el Libro de Eventos (obligatorio RD 1007/2023).
     */
    public static function registrarEvento($tipo, $idDocumento, $tablaOrigen, $descripcion, $datosExtra = null)
    {
        try {
            $pdo = ConexionDB::getInstancia()->getConexion();
            $stmt = $pdo->prepare(
                "INSERT INTO verifactu_eventos (tipo, id_documento, tabla_origen, descripcion, datos_extra) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$tipo, $idDocumento, $tablaOrigen, $descripcion, $datosExtra ? json_encode($datosExtra) : null]);
        } catch (Exception $e) {
            error_log('Verifactu::registrarEvento error: ' . $e->getMessage());
        }
    }

    // ======================== SUBSANACIÓN ========================

    /**
     * Subsana un documento rechazado: regenera XML con datos actuales y lo encola.
     * @return array ['success' => bool, 'message' => string]
     */
    public static function subsanarDocumento($idDocumento, $tablaOrigen)
    {
        try {
            require_once(__DIR__ . '/../model/Venta.php');

            $pdo = ConexionDB::getInstancia()->getConexion();

            // Buscar la venta en la tabla específica (tickets o facturas)
            $stmtVenta = $pdo->prepare("SELECT * FROM {$tablaOrigen} WHERE id = ?");
            $stmtVenta->execute([$idDocumento]);
            $fila = $stmtVenta->fetch(PDO::FETCH_ASSOC);

            if (!$fila) {
                return ['success' => false, 'message' => "Documento $idDocumento no encontrado en $tablaOrigen."];
            }

            // Cargar serie y numero desde ventas_ids
            $stmtIds = $pdo->prepare("SELECT serie, numero FROM ventas_ids WHERE id = ?");
            $stmtIds->execute([$idDocumento]);
            $ids = $stmtIds->fetch(PDO::FETCH_ASSOC);
            if ($ids) {
                $fila['serie'] = $ids['serie'];
                $fila['numero'] = $ids['numero'];
            }

            $venta = Venta::crearDesdeArray($fila);

            // Recuperar líneas de venta
            $stmtLines = $pdo->prepare("SELECT * FROM lineasVenta WHERE idVenta = ?");
            $stmtLines->execute([$idDocumento]);
            $lineas = $stmtLines->fetchAll(PDO::FETCH_ASSOC);

            // Validar antes de regenerar
            $validacion = self::validarDatosPreEnvio($venta, $lineas);
            if (!$validacion['valid']) {
                $msg = 'Subsanación rechazada: ' . json_encode($validacion['errors']);
                self::registrarEvento('validacion_fallida', $idDocumento, $tablaOrigen, $msg);
                return ['success' => false, 'message' => 'Validación fallida', 'errors' => $validacion['errors']];
            }

            // Regenerar XML
            $resultadoGen = self::generarXML($venta, $lineas);
            $xmlNuevo = $resultadoGen['xml'];
            $hashNuevo = $resultadoGen['huella'];

            // Actualizar hash, XML y estado en documento original
            $pdo->prepare("UPDATE {$tablaOrigen} SET xml_datos = ?, hash = ?, estado_aeat = 'error', error_aeat = 'Subsanado (Pendiente de envío manual)' WHERE id = ?")
                ->execute([$xmlNuevo, $hashNuevo, $idDocumento]);

            // Obtener Serie+Numero para la cola
            $stmtIds = $pdo->prepare("SELECT serie, numero FROM ventas_ids WHERE id = ?");
            $stmtIds->execute([$idDocumento]);
            $ids = $stmtIds->fetch(PDO::FETCH_ASSOC);
            $numDoc = ($ids['serie'] ?? '') . ($ids['numero'] ?? '');

            // Encolar como 'subsanado' (estado especial que no se procesa automáticamente)
            $colaId = self::encolarEnvio($idDocumento, $tablaOrigen, 'subsanacion', $xmlNuevo, 'Subsanado - Pendiente envío manual', null, false, $numDoc, 'subsanado');

            // LIMPIEZA: Borrar físicamente todos los intentos anteriores de este documento que fallaron
            $pdo->prepare("DELETE FROM verifactu_cola_envios WHERE id_documento = ? AND tabla_origen = ? AND id != ?")
                ->execute([$idDocumento, $tablaOrigen, $colaId]);

            self::registrarEvento(
                'subsanacion',
                $idDocumento,
                $tablaOrigen,
                "Documento subsanado y listo para envío manual (cola ID: $colaId)."
            );

            return ['success' => true, 'message' => 'Documento subsanado. Ahora debe enviarlo manualmente desde la cola.', 'cola_id' => $colaId];

        } catch (Exception $e) {
            error_log("Error en subsanarDocumento: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno: ' . $e->getMessage()];
        }
    }

    /**
     * Guarda el tiempo de cooldown AEAT (TiempoEsperaEnvio) como timestamp futuro.
     */
    public static function guardarCooldownAEAT($segundos)
    {
        if ($segundos <= 0)
            return;
        $pdo = ConexionDB::getInstancia()->getConexion();
        $stmt = $pdo->prepare("INSERT INTO configuracion_fiscal (clave, valor) VALUES ('AEAT_COOLDOWN_HASTA', DATE_ADD(NOW(), INTERVAL ? SECOND)) ON DUPLICATE KEY UPDATE valor = DATE_ADD(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$segundos, $segundos]);
        error_log("DEBUG Verifactu: Cooldown AEAT guardado. Segundos: {$segundos}s");
    }

    /**
     * Obtiene los segundos restantes de cooldown AEAT. 0 = puede enviar ya.
     */
    public static function getCooldownRestante()
    {
        $pdo = ConexionDB::getInstancia()->getConexion();
        // Obtener la diferencia en segundos entre el valor guardado y NOW() de la base de datos
        $stmt = $pdo->prepare("SELECT TIMESTAMPDIFF(SECOND, NOW(), valor) FROM configuracion_fiscal WHERE clave = 'AEAT_COOLDOWN_HASTA'");
        $stmt->execute();
        $diff = $stmt->fetchColumn();
        return ($diff && $diff > 0) ? (int) $diff : 0;
    }

    /**
     * Obtiene estadísticas de la cola de envíos.
     */
    public static function getEstadisticasCola()
    {
        $pdo = ConexionDB::getInstancia()->getConexion();
        $stats = [];

        $stmt = $pdo->query("SELECT estado, COUNT(*) as total FROM verifactu_cola_envios GROUP BY estado");
        $porEstado = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $porEstado[$r['estado']] = (int) $r['total'];
        }
        $stats['por_estado'] = $porEstado;
        $stats['pendientes'] = ($porEstado['pendiente'] ?? 0) + ($porEstado['error_temporal'] ?? 0);
        $stats['errores_permanentes'] = $porEstado['error_permanente'] ?? 0;

        $stmt = $pdo->query("SELECT COUNT(*) FROM verifactu_cola_envios WHERE estado = 'enviado' AND DATE(fecha_envio_exitoso) = CURDATE()");
        $stats['enviados_hoy'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM verifactu_cola_envios WHERE es_error_conexion = 1 AND estado IN ('pendiente','error_temporal')");
        $stats['sin_conexion'] = (int) $stmt->fetchColumn();

        // Cooldown AEAT
        $stats['cooldown_segundos'] = self::getCooldownRestante();

        return $stats;
    }
}