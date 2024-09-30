<?php


namespace GeorgPreissl\Projects\EventListener\DataContainer;

use Contao\CoreBundle\Slug\ValidCharacters;

class SettingsListener
{
    private $validCharacters;

    public function __construct(ValidCharacters $validCharacters = null)
    {
        $this->validCharacters = $validCharacters;
    }

    /**
     * On slug setting options callback
     *
     * @return array
     */
    public function onSlugSettingOptionsCallback()
    {
        return $this->validCharacters->getOptions();
    }
}
