<?php

namespace Maya\View;

use Maya\View\Traits\HasViewLoader;
use Maya\View\Traits\HasExtendsContent;
use Maya\View\Traits\HasIncludeContent;
use Exception;

class ViewBuilder
{
    use HasViewLoader, HasExtendsContent, HasIncludeContent;

    public $content;
    public array $vars = [];
    private array $skipSections = [];

    public function run($dir): void
    {
        $this->content = $this->viewLoader($dir);
        $this->extractSkipSections();
        $this->checkExtendsContent();
        $this->checkIncludesContent();
        $this->checkCSRF();
        $this->checkMethod();
        $this->checkOpenPHP();
        $this->checkPHPTag();
        $this->checkAuth();
        $this->checkGuest();
        $this->checkEcho();
        $this->checkRouteFinder();
        $this->checkIsEmpty();
        $this->checkDD();
        $this->checkDump();
        $this->checkError();
        $this->checkIsSet();
        $this->checkforeach();
        $this->checkCecked();
        $this->checkDisabled();
        $this->checkContinue();
        $this->checkBreak();
        $this->checkSelected();
        $this->checkReadOnly();
        $this->checkWhile();
        $this->checkFor();
        $this->checkConditional();
        $this->checkUnescapedEcho();
        $this->restoreSkipSections();
        Composer::setViews($this->viewNameArray);
        $this->vars = Composer::getVars();
    }

    private function extractSkipSections(): void
    {
        preg_match_all('/@skip(.*?)@endskip/s', $this->content, $matches);
        foreach ($matches[0] as $key => $match) {
            $placeholder = "__SKIP_PLACEHOLDER_{$key}__";
            $innerContent = $matches[1][$key];
            $this->skipSections[$placeholder] = $innerContent;
            $this->content = str_replace($match, $placeholder, $this->content);
        }
    }

    private function restoreSkipSections(): void
    {
        foreach ($this->skipSections as $placeholder => $innerContent) {
            $this->content = str_replace($placeholder, $innerContent, $this->content);
        }
    }

    private function checkEcho(): void
    {
        preg_match_all('/{{\s*(.*?)\s*}}/', $this->content, $matches);
        foreach ($matches[0] as $key => $match) {
            $expression = $matches[1][$key];
            $this->content = str_replace($match, "<?= htmlentities($expression) ?>", $this->content);
        }
    }
    private function checkOpenPHP(): void
    {
        preg_match_all('/{{{\s*(.*?)\s*}}}/', $this->content, $matches);
        foreach ($matches[0] as $key => $match) {
            $expression = $matches[1][$key];
            $this->content = str_replace($match, "<?php $expression ?>", $this->content);
        }
    }

    private function checkPHPTag()
    {
        $this->content = str_replace('@php', '<?php', $this->content);
        $this->content = str_replace('@endphp', '?>', $this->content);
    }

    private function checkAuth()
    {
        $this->content = str_replace('@auth', '<?php if(auth()->checkLogin()): ?>', $this->content);
        $this->content = str_replace('@endauth', '<?php endif; ?>', $this->content);
    }

    private function checkGuest()
    {
        $this->content = str_replace('@guest', '<?php if(!auth()->checkLogin()): ?>', $this->content);
        $this->content = str_replace('@endguest', '<?php endif; ?>', $this->content);
    }

    private function checkUnescapedEcho(): void
    {
        preg_match_all('/{!!\s*(.*?)\s*!!}/', $this->content, $matches);
        foreach ($matches[0] as $key => $match) {
            $expression = $matches[1][$key];
            $this->content = str_replace($match, "<?= $expression ?>", $this->content);
        }
    }

    private function checkforeach(): void
    {
        $this->content = preg_replace('/@foreach\s*\((.*)\)/', '<?php foreach($1): ?>', $this->content);
        $this->content = preg_replace('/@endforeach/', '<?php endforeach; ?>', $this->content);
        $this->content = preg_replace('/@forelse\s*\((.*?)\s+as\s+(.*?)\)\s*$/m', '<?php if (!empty($1)): foreach($1 as $2): ?>', $this->content);
        $this->content = preg_replace('/@empty/', '<?php endforeach; else: ?>', $this->content);
        $this->content = preg_replace('/@endforelse/', '<?php endif; ?>', $this->content);
    }

    private function checkIsEmpty(): void
    {
        $this->content = preg_replace('/@empty\s*\((.*)\)/', '<?php if (empty($1)): ?>', $this->content);
        $this->content = preg_replace('/@endempty/', '<?php endif; ?>', $this->content);
    }
    private function checkWhile(): void
    {
        $this->content = preg_replace('/@while\s*\((.*)\)/', '<?php while($1): ?>', $this->content);
        $this->content = preg_replace('/@endwhile/', '<?php endwhile; ?>', $this->content);
    }

    private function checkFor(): void
    {
        $this->content = preg_replace('/@for\s*\((.*)\)/', '<?php for($1): ?>', $this->content);
        $this->content = preg_replace('/@endfor/', '<?php endfor; ?>', $this->content);
    }

    private function checkDD(): void
    {
        $this->content = preg_replace('/@dd\s*\((.*)\)/', '<?php dd($1) ?>', $this->content);
    }

    private function checkDump(): void
    {
        $this->content = preg_replace('/@dump\s*\((.*)\)/', '<?php dump($1) ?>', $this->content);
    }

    private function checkRouteFinder(): void
    {
        $this->content = preg_replace('/@route\s*\((.*)\)/', '<?= htmlentities(route($1)) ?>', $this->content);
    }

    private function checkIsSet(): void
    {
        $this->content = preg_replace('/@isset\s*\((.*)\)/', '<?php if (isset($1)): ?>', $this->content);
        $this->content = preg_replace('/@endisset/', '<?php endif; ?>', $this->content);
    }

    private function checkError(): void
    {
        $this->content = preg_replace('/@error\s*\((.*)\)/', '<?php if (error()->exists($1)): ?>', $this->content);
        $this->content = preg_replace('/@enderror/', '<?php endif; ?>', $this->content);
    }


    private function checkConditional(): void
    {
        $this->content = str_replace('@endif', '<?php endif; ?>', $this->content);
        $this->content = preg_replace('/@elseif\s*\((.*)\)/s', '<?php elseif($1): ?>', $this->content);
        $this->content = str_replace('@else', '<?php else: ?>', $this->content);
        $this->content = preg_replace('/@if\s*\((.*)\)/s', '<?php if($1): ?>', $this->content);
    }

    private function checkCSRF(): void
    {
        $this->content = str_replace('@csrf', '<?= get_csrf_input() ?>', $this->content);
    }

    private function checkMethod(): void
    {
        $this->content = preg_replace('/@method\s*\((.*)\)/', '<?= get_methodField_input($1) ?>', $this->content);
    }
    private function checkCecked(): void
    {
        $this->content = preg_replace('/@checked\s*\((.*)\)/', '<?php echo ($1) ? "checked" : ""  ?>', $this->content);
    }
    private function checkSelected(): void
    {
        $this->content = preg_replace('/@selected\s*\((.*)\)/', '<?php echo ($1) ? "selected" : ""  ?>', $this->content);
    }
    private function checkDisabled(): void
    {
        $this->content = preg_replace('/@disabled\s*\((.*)\)/', '<?php echo ($1) ? "disabled" : ""  ?>', $this->content);
    }
    private function checkReadOnly(): void
    {
        $this->content = preg_replace('/@readonly\s*\((.*)\)/', '<?php echo ($1) ? "readonly" : ""  ?>', $this->content);
    }
    private function checkContinue(): void
    {
        $this->content = preg_replace('/@continue\s*\((.*)\)/', '<?php if ($1) continue; ?>', $this->content);
        $this->content = str_replace('@continue', '<?php continue; ?>', $this->content);
    }
    private function checkBreak(): void
    {
        $this->content = preg_replace('/@break\s*\((.*)\)/', '<?php if ($1) break; ?>', $this->content);
        $this->content = str_replace('@break', '<?php break; ?>', $this->content);
    }
}
