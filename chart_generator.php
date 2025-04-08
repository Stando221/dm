<?php
// includes/chart_generator.php

class ChartGenerator {
    public static function createDiscChart($data, $outputPath) {
        $width = 600;
        $height = 400;
        $image = imagecreatetruecolor($width, $height);
        
        // Background color
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
        
        // Center point
        $centerX = $width / 2;
        $centerY = $height / 2;
        $radius = 150;
        
        // Colors for each segment
        $colors = [
            imagecolorallocate($image, 255, 99, 132),   // D - Red
            imagecolorallocate($image, 54, 162, 235),   // I - Blue
            imagecolorallocate($image, 255, 206, 86),   // S - Yellow
            imagecolorallocate($image, 75, 192, 192)    // C - Teal
        ];
        
        // Calculate total for percentages
        $total = array_sum($data);
        if ($total == 0) $total = 1; // prevent division by zero
        
        // Draw doughnut chart
        $startAngle = 0;
        $i = 0;
        foreach (['D', 'I', 'S', 'C'] as $letter) {
            $value = $data[$letter] ?? 25;
            $endAngle = $startAngle + (360 * ($value / $total));
            
            // Draw outer arc
            imagefilledarc($image, $centerX, $centerY, $radius*2, $radius*2, 
                          $startAngle, $endAngle, $colors[$i], IMG_ARC_PIE);
            
            // Draw inner cutout (to make it a doughnut)
            $innerRadius = $radius * 0.6;
            imagefilledarc($image, $centerX, $centerY, $innerRadius*2, $innerRadius*2, 
                          $startAngle, $endAngle, $bgColor, IMG_ARC_PIE);
            
            // Add labels
            $midAngle = ($startAngle + $endAngle) / 2;
            $labelRadius = $radius * 0.8;
            $x = $centerX + $labelRadius * cos(deg2rad($midAngle));
            $y = $centerY + $labelRadius * sin(deg2rad($midAngle));
            
            $label = substr($letter, 0, 1) . ': ' . round($value) . '%';
            imagestring($image, 3, $x-10, $y-5, $label, imagecolorallocate($image, 0, 0, 0));
            
            $startAngle = $endAngle;
            $i++;
        }
        
        // Save image
        imagepng($image, $outputPath);
        imagedestroy($image);
    }
    
    public static function createMiChart($data, $outputPath) {
        $width = 800;
        $height = 400;
        $image = imagecreatetruecolor($width, $height);
        
        // Background color
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
        
        // Chart area
        $chartX = 100;
        $chartY = 50;
        $chartWidth = $width - 200;
        $chartHeight = $height - 100;
        
        // Bar settings
        $barWidth = 60;
        $gap = 30;
        $x = $chartX;
        
        $colors = [
            imagecolorallocate($image, 255, 99, 132),
            imagecolorallocate($image, 54, 162, 235),
            imagecolorallocate($image, 255, 206, 86),
            imagecolorallocate($image, 75, 192, 192),
            imagecolorallocate($image, 153, 102, 255),
            imagecolorallocate($image, 255, 159, 64),
            imagecolorallocate($image, 199, 199, 199),
            imagecolorallocate($image, 83, 102, 255)
        ];
        
        $i = 0;
        foreach ($data as $label => $value) {
            $barHeight = ($value / 100) * $chartHeight;
            $y = $chartY + $chartHeight - $barHeight;
            
            // Draw bar
            imagefilledrectangle($image, $x, $y, $x + $barWidth, $chartY + $chartHeight, $colors[$i % count($colors)]);
            
            // Draw label
            $textColor = imagecolorallocate($image, 0, 0, 0);
            imagestring($image, 3, $x, $chartY + $chartHeight + 5, substr($label, 0, 10), $textColor);
            
            // Draw value
            imagestring($image, 3, $x + 15, $y - 15, $value . '%', $textColor);
            
            $x += $barWidth + $gap;
            $i++;
        }
        
        // Save image
        imagepng($image, $outputPath);
        imagedestroy($image);
    }
}
?>