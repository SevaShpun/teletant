<?php


namespace Askoldex\Teletant\Addons;


use Askoldex\Teletant\Context;
use Askoldex\Teletant\Exception\MenuxException;

class Menux
{
    const KEYBOARD = 'keyboard';
    const INLINE_KEYBOARD = 'inline_keyboard';

    private static $associations = [];
    /**
     * @var Menux[] $links
     */
    private static $links = [];

    private static $default = self::KEYBOARD;
    private static $defaultProperties = [];
    private $name;
    private $id;
    private $source;
    private $rowIndex = 0;
    private $firstRow = true;
    private $type = '';

    private function __construct($name, $id)
    {
        $this->name = $name;
        $this->id = $id;
        $this->type = self::$default;
        $this->source = self::$defaultProperties;
    }

    /**
     * @return $this
     */
    public function inline(): self
    {
        $this->type = 'inline_keyboard';
        return $this;
    }

    /**
     * @param $button
     */
    private function addButton($button): void
    {
        if (is_array($button)) {
            if ($this->type == self::KEYBOARD and isset($button['callback_data'])) {
                unset($button['callback_data']);
            }
            $this->source[$this->type][$this->rowIndex][] = $button;
        } else {
            if ($this->type != self::KEYBOARD)
                $this->source[$this->type][$this->rowIndex][] = ['text' => $button, 'callback_data' => $button];
            else
                $this->source[$this->type][$this->rowIndex][] = ['text' => $button];
        }
    }

    /**
     * @param mixed ...$buttons
     * @return Menux
     */
    public function row(...$buttons): self
    {
        if ($this->firstRow) $this->firstRow = false;
        else $this->rowIndex++;

        if (count($buttons) > 0) {
            foreach ($buttons as $button) {
                $this->addButton($button);
            }
        }
        return $this;
    }

    /**
     * @param array $buttons
     * @return Menux
     */
    public function arrayRow(array $buttons): self
    {
        call_user_func_array([$this, 'row'], $buttons);
        return $this;
    }

    /**
     * @param array $buttons
     * @param int $inLine
     * @return Menux
     */
    public function autoRows(array $buttons, int $inLine): self
    {
        $rows = array_chunk($buttons, $inLine);
        foreach ($rows as $row) {
            $this->arrayRow($row);
        }
        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @return Menux
     */
    public function property(string $name, string $value): self
    {
        $this->source[$name] = $value;
        return $this;
    }

    public function build() {
        return json_encode($this->source);
    }

    public function getAsObject()
    {
        return $this->source;
    }

    public function __toString()
    {
        return $this->build();
    }

    /**
     * @param string $text
     * @param string|null $data
     * @return Menux
     */
    public function btn(string $text, string $data = null): self
    {
        $this->addButton(self::Button($text, $data));
        return $this;
    }

    /**
     * @param string $text
     * @return Menux
     */
    public function lBtn(string $text): self
    {
        $this->addButton(self::LocationButton($text));
        return $this;
    }

    /**
     * @param string $text
     * @return Menux
     */
    public function cBtn(string $text): self
    {
        $this->addButton(self::ContactButton($text));
        return $this;
    }

    /**
     * @param string $text
     * @param string $url
     * @return Menux
     */
    public function uBtn(string $text, string $url): self
    {
        $this->addButton(self::UrlButton($text, $url));
        return $this;
    }

    public function menu(string $text, Menux $menu): self
    {
        $this->addButton(self::MenuButton($text, $this, $menu));
        return $this;
    }

    /**
     * @var Menux[] $menus
     */
    private static $menus;
    private static $index = 0;

    /**
     * @param string $name
     * @param string|null $key
     * @return self
     * @throws MenuxException
     */
    public static function Create(string $name, string $key = null): self
    {
        if($key != null) {
            if(array_key_exists($key, self::$links))
                throw new MenuxException('Key "' . $key . '" already exists');
            self::$links[$key] = &self::$menus[self::$index];
        }
        return self::$menus[self::$index] = new self($name, self::$index++);
    }

    /**
     * @param string $key
     * @return Menux
     * @throws MenuxException
     */
    public static function Get(string $key): self
    {
        $menu = self::$links[$key];
        if($menu instanceof self)
            return self::$links[$key];
        else
            throw new MenuxException('Key "' . $key . '" is undefined');
    }

    /**
     * @param string|null $type
     */
    public static function DefaultType(string $type): void
    {
        self::$default = $type;
    }

    /**
     * @param array $properties
     */
    public static function DefaultProperties(array $properties): void
    {
        self::$defaultProperties = $properties;
    }

    /**
     * @param string $text
     * @param string|null $data
     * @return array
     */
    public static function Button(string $text, string $data = null): array
    {
        if (is_null($data)) $data = $text;
        return ['text' => $text, 'callback_data' => $data];
    }

    /**
     * @param string $text
     * @return array
     */
    public static function LocationButton(string $text): array
    {
        return ['text' => $text, 'request_location' => true];
    }

    /**
     * @param string $text
     * @return array
     */
    public static function ContactButton(string $text): array
    {
        return ['text' => $text, 'request_contact' => true];
    }

    /**
     * @param string $text
     * @param string $url
     * @return array
     */
    public static function UrlButton(string $text, string $url): array
    {
        return ['text' => $text, 'url' => $url];
    }

    /**
     * @param string $text
     * @return array
     */
    public static function PayButton(string $text): array
    {
        return ['text' => $text, 'pay' => true];
    }

    /**
     * @param string $text
     * @param Menux $withMenu
     * @param Menux $toMenu
     * @return array
     */
    public static function MenuButton(string $text, Menux $withMenu, Menux $toMenu): array
    {
        if($withMenu->type == self::KEYBOARD) {
            self::$associations[$text] = [$toMenu->id, $withMenu->id];
            return self::Button($text);
        } else return self::Button($text, 'menux/' . $toMenu->id . '/' . $withMenu->id);
    }

    /**
     * @param bool $selective
     * @return string
     */
    public static function Delete(bool $selective = false): string
    {
        return json_encode(['remove_keyboard' => true, 'selective' => $selective]);
    }

    public static function Middleware()
    {
        return function (Context $ctx, $next) {
            if(!$ctx->callbackQuery()->isEmpty()) {
                $data = explode('/', $ctx->callbackQuery()->data(), 3);
                if(count($data) == 3 and $data[0] == 'menux') {
                    $toMenu = self::$menus[$data[1]];
                    $withMenu = self::$menus[$data[2]];
                    if($withMenu->type == self::KEYBOARD) {
                        $ctx->reply($toMenu->name, $toMenu);
                    } else {
                        if($toMenu->type == self::KEYBOARD) {
                            $ctx->reply($toMenu->name, $toMenu);
                        } else $ctx->editSelf($toMenu->name, $toMenu);
                    }
                    return true;
                }
            }
            if(count(self::$associations) > 0 and $ctx->getText() != '') {
                if(isset(self::$associations[$ctx->getText()])) {
                    $menus = self::$associations[$ctx->getText()];
                    $toMenu = self::$menus[$menus[0]];
                    $ctx->reply($toMenu->name, $toMenu);
                    return true;
                }
            }
            return $next($ctx);
        };
    }

    public static function dump()
    {
        var_dump(self::$menus, self::$index);
    }

}