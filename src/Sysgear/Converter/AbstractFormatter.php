<?php

/*
 * This file is part of the Sysgear package.
*
* (c) Martijn Evers <mevers47@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/


namespace Sysgear\Converter;

abstract class AbstractFormatter implements FormatterInterface
{
    /**
     * @var \Sysgear\Converter\FormatCollection
     */
    protected $formats;

    /**
     *
     * @param FormatCollection $formats
     */
    public function __construct(FormatCollection $formats)
    {
        $this->formats = $formats;
    }

    /**
     * (non-PHPdoc)
     * @see \Sysgear\Converter\FormatterInterface::setFormats()
     */
    public function setFormats(FormatCollection $formats)
    {
        $this->formats = $formats;
    }

    /**
     * (non-PHPdoc)
     * @see \Sysgear\Converter\FormatterInterface::getFormats()
     */
    public function getFormats()
    {
        return $this->formats;
    }
}