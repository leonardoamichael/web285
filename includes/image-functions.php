<?php

/**
 * Resize and compress an uploaded recipe image.
 *
 * Accepts JPG, PNG, and WebP uploads, then saves a compressed JPG.
 *
 * @param string $tmp_path Temporary uploaded file path
 * @param string $target_path Final saved JPG path
 * @param int $max_width Maximum output width
 * @param int $quality JPG quality, 0-100
 * @return bool
 */
function save_compressed_recipe_image($tmp_path, $target_path, $max_width = 900, $quality = 78)
{
  $image_info = getimagesize($tmp_path);

  if ($image_info === false) {
    return false;
  }

  $mime = $image_info['mime'];

  switch ($mime) {
    case 'image/jpeg':
      $source = imagecreatefromjpeg($tmp_path);
      break;

    case 'image/png':
      $source = imagecreatefrompng($tmp_path);
      break;

    case 'image/webp':
      $source = imagecreatefromwebp($tmp_path);
      break;

    default:
      return false;
  }

  if (!$source) {
    return false;
  }

  $original_width  = imagesx($source);
  $original_height = imagesy($source);

  if ($original_width <= $max_width) {
    $new_width  = $original_width;
    $new_height = $original_height;
  } else {
    $new_width  = $max_width;
    $new_height = (int) ($original_height * ($new_width / $original_width));
  }

  $resized = imagecreatetruecolor($new_width, $new_height);

  /*
   * Fill background white so transparent PNG/WebP images do not become black.
   */
  $white = imagecolorallocate($resized, 255, 255, 255);
  imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $white);

  imagecopyresampled(
    $resized,
    $source,
    0,
    0,
    0,
    0,
    $new_width,
    $new_height,
    $original_width,
    $original_height
  );

  $saved = imagejpeg($resized, $target_path, $quality);

  imagedestroy($source);
  imagedestroy($resized);

  return $saved;
}