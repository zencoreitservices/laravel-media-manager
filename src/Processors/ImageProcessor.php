<?php

namespace Zencoreitservices\MediaManager\Processors;

use Intervention\Image\Image;
use Intervention\Image\ImageManager;

class ImageProcessor
{
    protected $imageManager;
    protected $image;

    public function __construct($path)
    {
        $this->imageManager = new ImageManager(['driver' => config('media-manager.image-driver')]);
        $this->image = $this->imageManager->make($path);
    }

    public function newImage($path)
    {
        unset($this->image);
        $this->image = $this->imageManager->make($path);
        return $this;
    }

    public function resize($width = null, $height = null, $fit = 'cover')
    {
        if ((is_null($width) || $width == 'auto') && (is_null($height) || $height == 'auto')) {
            throw new \Exception('Width or height is required');
        }

        // Resize by height
        if (is_null($width) || $width == 'auto') {
            $this->image->heighten($height, function ($constraint) {
                $constraint->aspectRatio();
            });

            return $this;
        }

        // Resize by width
        if (is_null($height) || $height == 'auto') {
            $this->image->widen($width, function ($constraint) {
                $constraint->aspectRatio();
            });

            return $this;
        }

        if ($this->image->width() > $width && $this->image->height() > $height) {
            // width bigger
            // height bigger
            $predictHeight = $this->calcRatio($this->image->height(), $width, $this->image->width());
            if ($fit == 'cover') {
                if ($predictHeight >= $height) {
                    $this->image->resize($width, $predictHeight);
                } elseif ($predictHeight < $height) {
                    $this->image->resize(floor($this->calcRatio($this->image->width(), $height, $this->image->height())), $height);
                }
            } elseif ($fit == 'contain') {
                if ($predictHeight > $height) {
                    $this->image->resize(floor($this->calcRatio($this->image->width(), $height, $this->image->height())), $height);
                } elseif ($predictHeight <= $height) {
                    $this->image->resize($width, $predictHeight);
                }
                $this->image->resizeCanvas($width, $height, 'center', false, config('media-manager.background-color', '#FFFFFF'));
            }

            $this->image->crop($width, $height);
        } elseif ($this->image->width() < $width && $this->image->height() < $height) {
            // width smaller
            // height smaller
            $this->image->resizeCanvas($width, $height, 'center', false, config('media-manager.background-color', '#FFFFFF'));
        } elseif ($this->image->width() < $width && $this->image->height() > $height) {
            // width smaller
            // height bigger
            if ($fit == 'cover') {
                $this->image->crop($this->image->width(), $height)
                    ->resizeCanvas($width, $height, 'center', false, config('media-manager.background-color', '#FFFFFF'));
            } elseif ($fit == 'contain') {
                $this->image->resize(floor($this->calcRatio($this->image->width(), $height, $this->image->height())), $height)
                    ->resizeCanvas($width, $height, 'center', false, config('media-manager.background-color', '#FFFFFF'));
            }
        } elseif ($this->image->width() > $width && $this->image->height() < $height) {
            // width bigger
            // height smaller
            if ($fit == 'cover') {
                $this->image->crop($width, $this->image->height())
                    ->resizeCanvas($width, $height, 'center', false, config('media-manager.background-color', '#FFFFFF'));
            } elseif ($fit == 'contain') {
                $this->image->resize($width, floor($this->calcRatio($this->image->height(), $width, $this->image->width())))
                    ->resizeCanvas($width, $height, 'center', false, config('media-manager.background-color', '#FFFFFF'));
            }
        }

        return $this;
    }

    public function crop($width, $height, $x, $y)
    {
        $this->image->crop((int)$width, (int)$height, (int)$x, (int)$y);
        
        return $this;
    }

    public function save($path)
    {
        $this->image->save($path);
    }

    protected function calcRatio($a, $b, $c)
    {
        return (int)(($a * $b) / $c);
    }
}