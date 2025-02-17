<?php
namespace Src;

class Charts {

    private $font;

    public function __construct($font = 'arial') {
        $this->font = __DIR__ . '/../data/' . $font . '.ttf';
    }

    public function iRatingBars($iratings,$title) {
        $sof_calc = [];
        foreach ($iratings as $irating) {
            $sof_calc[] = $irating[1];
        }
        $sof = Helper::calculaSoF($sof_calc);
        // Configuración del gráfico
        $width = 1100;
        $height = 450;
        $margin = 50;
        $bar_width = 12;
        $bar_spacing = 5;
        $max_value = max(array_column($iratings, 1));

        // Calcular el promedio de iRating
        $total_bars = count($iratings);
        $avg_value = $sof;

        // Crear la imagen
        $image = imagecreatetruecolor($width, $height);

        // Colores
        $background_color = imagecolorallocate($image, 31, 41, 55); // Fondo
        $bar_colors = [
            imagecolorallocate($image, 50, 170, 110),     // Colo 1
            imagecolorallocate($image, 0, 180, 200),   // Color 2
            imagecolorallocate($image, 0, 102, 204)    // Color 3
        ];
        $border_color = $background_color;
        $text_color = imagecolorallocate($image, 255, 255, 255);
        $grid_color = imagecolorallocate($image, 70, 70, 70);
        $line_color = imagecolorallocate($image, 150, 150, 150); // Línea SoF
        $line_color_sof = imagecolorallocate($image, 214, 212, 66);
        

        // Rellenar fondo
        imagefill($image, 0, 0, $background_color);

        // Dibujar líneas de la grilla
        $step = 1000;
        if ($max_value < 6000) {
            $step = 500;
        } elseif ($max_value < 3000) {
            $step = 250;
        }
        for ($i = 0; $i <= $max_value; $i += $step) {
            $y = $height - $margin - ($i / $max_value) * ($height - 2 * $margin);
            imageline($image, $margin, $y, $width - $margin + ($bar_width + $bar_spacing), $y, $grid_color);
            $text_x = $this->horizontalAlign('right', 0, $margin-5, $this->font, 10, $i);
            imagettftext($image, 10, 0, $text_x, $y+4, $text_color, $this->font, $i);
        }

        // Dibujar las barras
        $x = $margin + $bar_spacing;
        $points = [];
        $total_width = $total_bars * ($bar_width + $bar_spacing) - $bar_spacing - ($bar_width/2);
        $scale = ($width - 2 * $margin) / $total_width;

        // Ajustar el tamaño de la fuente según la cantidad de barras
        $font_size = Self::calculaTamañoFuente($total_bars);

        foreach ($iratings as $index => $data) {
            list($name, $value) = $data;
            $bar_height = ($value / $max_value) * ($height - 2 * $margin);
            $x1 = $x;
            $y1 = $height - $margin - $bar_height;
            $x2 = $x1 + $bar_width * $scale;
            $y2 = $height - $margin;

            $color_index = $index % count($bar_colors);
            imagefilledrectangle($image, $x1, $y1, $x2, $y2, $bar_colors[$color_index]);
            imagerectangle($image, $x1, $y1, $x2, $y2, $border_color);

            // Alinear el texto horizontalmente
            $text_x = $this->horizontalAlign('center',$x1, $x2, $this->font, $font_size, $value);

            imagettftext($image, $font_size, 0, $text_x, $y1 - 5, $text_color, $this->font, $value);

            // Guardar puntos para la línea promedio
            $points[] = [$x1 + ($bar_width * $scale / 2), $y1];

            $x += ($bar_width + $bar_spacing) * $scale;
        }

        // Dibujar línea promedio (dasheada)
        $dashed_line = array_fill(0, 12, $line_color_sof);
        $dashed_line = array_merge($dashed_line, array_fill(0, 5, IMG_COLOR_TRANSPARENT));

        imagesetstyle($image, $dashed_line);
        imageline($image, $margin, $height - $margin - ($avg_value / $max_value) * ($height - 2 * $margin), $width - $margin + ($bar_width + $bar_spacing), $height - $margin - ($avg_value / $max_value) * ($height - 2 * $margin), IMG_COLOR_STYLED);
        $text_x = $this->horizontalAlign('right', $margin, $width - $margin + ($bar_width + $bar_spacing), $this->font, 10, "SoF: $avg_value");
        imagettftext($image, $font_size, 0, $text_x, $height - $margin - ($avg_value / $max_value) * ($height - 2 * $margin) - 5, $text_color, $this->font, "SoF: $avg_value");
        
        // Etiquetas y título
        //imagestring($image, 5, $width / 2 - 80, 10, $title, $text_color);
        $text_x = $this->horizontalAlign('center', $margin, $width - $margin, $this->font, 20, $title);
        imagettftext($image, 18, 0, $text_x, 30, $text_color, $this->font, $title);
        
        // texto de marca de agua
        $marca_agua = "iRacing-fb-bot";
        $text_x = $this->horizontalAlign('right', $margin, $width - $margin + ($bar_width + $bar_spacing), $this->font, 9, $marca_agua);
        imagettftext($image, 9, 0, $text_x + 5, $height - $margin + 16, imagecolorallocate($image, 165, 165, 165), $this->font, $marca_agua);
        return $image;
    }  

    public static function calculaTamañoFuente($total_barras) {
        // Definir los valores mínimos y máximos
        $min_barras = 1;
        $max_barras = 20;
        $min_fuente = 20;
        $max_fuente = 10;
        
        // Asegurar que el número de barras esté dentro del rango esperado
        $total_barras = max($min_barras, min($total_barras, $max_barras));
        
        // Interpolación lineal
        $tamaño_fuente = $min_fuente - (($total_barras - $min_barras) / ($max_barras - $min_barras)) * ($min_fuente - $max_fuente);
        
        return round($tamaño_fuente, 2); // Redondear para mayor precisión
    }

    public function horizontalAlign($align, $x1, $x2, $font, $font_size, $text) {
        $text_box = imagettfbbox($font_size, 0, $font, $text);
        $text_width = $text_box[2] - $text_box[0];
        if ($align == 'center') {
            return $x1 + (($x2 - $x1) / 2) - ($text_width / 2);
        } elseif ($align == 'right') {
            return $x2 - $text_width;
        } else {
            return $x1;
        }
    }
}
