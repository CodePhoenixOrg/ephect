<?php

namespace Ephect\Components;

use Ephect\ElementUtils;
use Ephect\IO\Utils;
use Ephect\Registry\ComponentRegistry;
use Ephect\Registry\PluginRegistry;

class Plugin extends AbstractFileComponent
{
    
    public function load(?string $filename = null): bool
    {
        $result = false;
        $this->filename = $filename ?: '';

        $this->code = Utils::safeRead(PLUGINS_ROOT . $this->filename);

        [$this->namespace, $this->function, $this->bodyStartsAt] = ElementUtils::getFunctionDefinition($this->code);
        if($this->function === null) {
            [$this->namespace, $this->function, $this->bodyStartsAt] = ElementUtils::getClassDefinition($this->code);
        } 
        $result = $this->code !== null;

        return  $result;
    }

    public function analyse(): void
    {
        parent::analyse();

        PluginRegistry::write($this->getFullyQualifiedFunction(), $this->getSourceFilename());
        ComponentRegistry::safeWrite($this->getFunction(), $this->getFullyQualifiedFunction());
    }

    public function parse(): void
    {
        parent::parse();

        $this->cacheHtml();
    }

}
