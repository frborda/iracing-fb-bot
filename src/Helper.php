<?php
namespace Src;

class Helper {

    public function __construct() {
    }

    public static function calculaSoF($iratings) {
        $valorFijo = 1600 / log(2);
        $cantidad = count($iratings);
        rsort($iratings);
        $sof_exponencial = 0;
        for ($i = 0; $i < count($iratings); $i++) {
            $irating_original = $iratings[$i];
            $tmp = exp(-($irating_original) / $valorFijo);
            $sof_exponencial += $tmp;
        }
        return round($valorFijo * log($cantidad / $sof_exponencial), 0);
    }

    public static function completaEspacios($texto,$cantidad) {
        $output = '';
        $espacios = $cantidad - mb_strlen($texto);
        for ($i = 0; $i < $espacios; $i++) {
            $output .= " ";
        }
        return $output;
    }

    public static function completaEspaciosTexto($texto,$cantidad) {
        $output = '';
        $espacios = $cantidad - mb_strlen($texto);
        for ($i = 0; $i < $espacios; $i++) {
            $output .= " ";
        }
        return $texto.$output;
    }

    public static function getCarName($id) {
        $json = file_get_contents(JSON_CARS);
        $data = json_decode($json, true);
        $name = '';
        foreach ($data as $car) {
            if ($car['car_id'] == $id) {
                $name = $car['car_name'];
                break;
            }
        }
        return $name;
    }
    
}
