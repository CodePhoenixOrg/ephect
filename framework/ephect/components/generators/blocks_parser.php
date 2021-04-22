<?php

namespace Ephect\Components\Generators;

use Ephect\Components\Component;
use Ephect\Components\FileComponentInterface;
use Ephect\IO\Utils;
use Ephect\Registry\ComponentRegistry;

class BlocksParser extends Parser
{
    protected $blockComponent;

    public function __construct(FileComponentInterface $comp)
    {
        $this->blockComponent = $comp;
        parent::__construct($comp);
    }

    public function doBlocks(): ?string
    {
        ComponentRegistry::uncache();
        $functionFilename = null;

        $doc = new ComponentDocument($this->blockComponent);
        $doc->matchAll();

        $firstMatch = $doc->getNextMatch();
        if ($firstMatch === null || !$firstMatch->hasCloser()) {
            return null;
        }

        $functionName = $firstMatch->getName();

        $parentComponent = new Component($functionName);
        if(!$parentComponent->load()) {
            return null;
        }

        $parentFilename = $parentComponent->getFlattenSourceFilename();
        $parentDoc = new ComponentDocument($parentComponent);
        $parentDoc->matchAll();

        $parentHtml = $parentDoc->replaceMatches($doc, $this->html);

        if ($parentHtml !== '') {
            Utils::safeWrite(CACHE_DIR . $parentFilename, $parentHtml);
            Utils::safeWrite(CACHE_DIR . $this->blockComponent->getFlattenFilename(), $this->html);
        }

        if ($doc->getCount() > 0) {
            ComponentRegistry::cache();
        }

        return $functionFilename;
    }
}
