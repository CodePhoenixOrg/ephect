<?php

namespace Ephect\Components\Generators;

use Ephect\Components\ComponentFactory;
use Ephect\Components\ComponentInterface;
use Ephect\Registry\CodeRegistry;
use Ephect\Registry\ComponentRegistry;

class Maker
{
    private $html = '';
    private $component = null;
    private $parentHTML = '';

    public function __construct(ComponentInterface $comp)
    {
        $this->component = $comp;
        $this->html = $comp->getCode();
        $this->parentHTML = $comp->getParentHTML();
    }

    public function getHtml()
    {
        return $this->html;
    }

    public function doCache(): bool
    {
        return CodeRegistry::cache();
    }

    public function doUncache(): bool
    {
        return CodeRegistry::uncache();
    }

    public function makeChildren(string $componentText, string $componentName, ?array $componentArgs, string $componentBody, string $componentBoundaries, ?string &$subject): bool
    {
        ComponentRegistry::uncache();

        $motherUID = $this->component->getMotherUID();
        $fqClass = ComponentRegistry::read($componentName);
        $component = ComponentFactory::create($fqClass, $motherUID);

        $uid = $component->getUID();
        $motherUID = $component->getMotherUID();
        $namespace = $component->getNamespace();

        $entity = $component->getEntity();
        $useChildren = $entity !== null && $entity->hasChildren() ? " use (\$children) " : ' ';

        $args = $componentArgs === null ? null : $this->doArgumentsToString($componentArgs);
        $args = (($args === null) ? "null" : $args);

        $children = <<<CHILDREN

        $componentBody
        
        CHILDREN;

        $className = $this->component->getFunction() ?: $componentName;
        $classArgs = 'null';

        $fqComponentName = '\\' . ComponentRegistry::read($componentName);

        $params = "['props' => $args, 'callback' => function()$useChildren{?>\n$children<?php\n}, 'parent' => ['name' => '$className', 'props' => $classArgs, 'uid' => '$uid', 'motherUID' => '$motherUID']]";

        $componentRender = "<?php \$fn = $fqComponentName($params); \$fn(); ?>";

        $subject = str_replace($componentText, $componentRender, $subject);

        $result = $subject !== null;

        return $result;
    }

    public static function doArgumentsToString(array $componentArgs): ?string
    {
        $result = '';

        foreach ($componentArgs as $key => $value) {

            if (is_array($value)) {
                $value = json_encode($value);
            }
            $result .= '"' . $key . '" => "' . urlencode($value) . '", ';
        }
        $result = ($result === '') ? null : '[' . $result . ']';

        return $result;
    }
}
