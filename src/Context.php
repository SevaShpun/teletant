<?php

namespace Askoldex\Teletant;


use Askoldex\Formatter\Formatter;
use Askoldex\Teletant\Entities\CallbackQuery;
use Askoldex\Teletant\Entities\Chat;
use Askoldex\Teletant\Entities\ChosenInlineResult;
use Askoldex\Teletant\Entities\File;
use Askoldex\Teletant\Entities\InlineQuery;
use Askoldex\Teletant\Entities\Message;
use Askoldex\Teletant\Entities\Messages;
use Askoldex\Teletant\Entities\PreCheckoutQuery;
use Askoldex\Teletant\Entities\ShippingQuery;
use Askoldex\Teletant\Entities\Sticker;
use Askoldex\Teletant\Entities\Update;
use Askoldex\Teletant\Entities\User;
use Askoldex\Teletant\Interfaces\StorageInterface;
use Askoldex\Teletant\States\Scene;
use Askoldex\Teletant\States\Stage;

class Context
{
    /**
     * @var Update $update
     */
    private $update;

    /**
     * @var Api $api
     */
    private $api;

    private $storage;
    private $formatter;
    private $stage;
    private $variables;

    public function __construct(Update $update, Api $api)
    {
        $this->update = $update;
        $this->api = $api;
        $this->setFormatter(new Formatter());
        $this->bootFormatterDefaultAssociations();
    }

    /**
     * @param array $variables
     * @return $this
     */
    public function setVariables(array $variables)
    {
        $this->variables = $variables;
        return $this;
    }

    /**
     * @param string $variable
     * @param string|null $default
     * @return string
     */
    public function var(string $variable, string $default = '')
    {
        return $this->variables[$variable] ?? $default;
    }

    /**
     * @return Api
     */
    public function Api(): Api
    {
        return $this->api;
    }

    /**
     * @return Stage|null
     */
    public function Stage(): ?Stage
    {
        return $this->stage instanceof Stage ? $this->stage : null;
    }

    /**
     * @param Stage $stage
     * @return self
     */
    public function setStage(Stage $stage): self
    {
        $this->stage = $stage;
        return $this;
    }

    /**
     * @return StorageInterface|null
     */
    public function Storage(): ?StorageInterface
    {
        return $this->storage instanceof StorageInterface ? $this->storage : null;
    }

    /**
     * @param StorageInterface $storage
     * @return self
     */
    public function setStorage(StorageInterface $storage): self
    {
        $this->storage = $storage;
        return $this;
    }

    /**
     * @return Formatter
     */
    public function Formatter(): Formatter
    {
        return $this->formatter;
    }

    /**
     * @return $this
     */
    private function bootFormatterDefaultAssociations()
    {
        $this->Formatter()->associate('update', $this->update());
        $this->Formatter()->associate('message', $this->getMessage());
        $this->Formatter()->associate('user', $this->getFrom());
        $this->Formatter()->associate('username', $this->getUsername());
        $this->Formatter()->associate('chat', $this->getChat());
        $this->Formatter()->associate('text', $this->getText());
        $this->Formatter()->associate('lowertext', $this->getLowerText());
        return $this;
    }

    /**
     * @param string $variable
     * @param $object
     * @return self
     */
    public function with(string $variable, $object): self
    {
        $this->Formatter()->associate($variable, $object);
        return $this;
    }

    /**
     * @param Formatter $formatter
     * @return self
     */
    public function setFormatter(Formatter $formatter): self
    {
        $this->formatter = $formatter;
        return $this;
    }

    /**
     * @param string|Scene $scene
     * @throws Exception\StageException
     */
    public function enter($scene)
    {
        $sceneName = $scene instanceof Scene ? $scene->getName() : $scene;
        $this->Stage()->enterScene($this, $sceneName);
    }

    /**
     * @throws Exception\StageException
     */
    public function leave()
    {
        $this->Stage()->leaveScene($this);
    }

    /**
     * @return Update
     */
    public function update(): Update
    {
        return $this->update;
    }

    public function getMessage(): Message
    {
        if (!$this->editedMessage()->isEmpty())
            return $this->editedMessage();
        elseif (!$this->callbackQuery()->message()->isEmpty())
            return $this->callbackQuery()->message();
        elseif (!$this->channelPost()->isEmpty())
            return $this->channelPost();
        else return $this->update()->message();
    }

    public function editedMessage(): Message
    {
        return $this->update()->editedMessage();
    }

    public function inlineQuery(): InlineQuery
    {
        return $this->update()->inlineQuery();
    }

    public function shippingQuery(): ShippingQuery
    {
        return $this->update()->shippingQuery();
    }

    public function preCheckoutQuery(): PreCheckoutQuery
    {
        return $this->update()->preCheckoutQuery();
    }

    public function chosenInlineResult(): ChosenInlineResult
    {
        return $this->update()->chosenInlineResult();
    }

    public function channelPost(): Message
    {
        return $this->update()->channelPost();
    }

    public function editedChannelPost(): Message
    {
        return $this->update()->editedChannelPost();
    }

    public function callbackQuery(): CallbackQuery
    {
        return $this->update()->callbackQuery();
    }

    public function getFrom(): User
    {
        if(!$this->update()->message()->isEmpty())                  return $this->update()->message()->from();
        elseif(!$this->update()->editedMessage()->isEmpty())        return $this->update()->editedMessage()->from();
        elseif(!$this->update()->callbackQuery()->isEmpty())        return $this->update()->callbackQuery()->from();
        elseif(!$this->update()->inlineQuery()->isEmpty())          return $this->update()->inlineQuery()->from();
        elseif(!$this->update()->channelPost()->isEmpty())          return $this->update()->channelPost()->from();
        elseif(!$this->update()->editedChannelPost()->isEmpty())    return $this->update()->editedChannelPost()->from();
        elseif(!$this->update()->shippingQuery()->isEmpty())        return $this->update()->shippingQuery()->from();
        elseif(!$this->update()->preCheckoutQuery()->isEmpty())     return $this->update()->preCheckoutQuery()->from();
        elseif(!$this->update()->chosenInlineResult()->isEmpty())   return $this->update()->chosenInlineResult()->from();
        else                                                        return new User([]);
    }

    public function getChat(): Chat
    {
        return $this->getMessage()->chat();
    }

    public function getChatType(): string
    {
        return $this->getMessage()->chat()->type() ?? '';
    }

    public function getText(): string
    {
        return $this->getMessage()->text() ?? '';
    }

    public function getLowerText(): string
    {
        return mb_strtolower($this->getText());
    }

    public function getSticker(): Sticker
    {
        return $this->getMessage()->sticker();
    }

    public function getChatID(): int
    {
        return $this->getChat()->id();
    }

    public function getMessageID(): int
    {
        return $this->getMessage()->messageId();
    }

    public function getCallbackID(): int
    {
        return $this->callbackQuery()->id();
    }

    public function getUserID(): int
    {
        return $this->getFrom()->id();
    }

    public function getUsername(): string
    {
        return $this->getFrom()->username() ?? '';
    }

    public function getFromIsBot(): bool
    {
        return $this->getFrom()->isBot();
    }

    public function getFirstName(): string
    {
        return $this->getFrom()->firstName() ?? '';
    }

    public function getLastName(): string
    {
        return $this->getFrom()->lastName() ?? '';
    }

    public function getFullName(): string
    {
        return $this->getFirstName() . ($this->getLastName() != '' ? ' ' . $this->getLastName() : '');
    }

    public function getLangCode(): string
    {
        return $this->getFrom()->languageCode();
    }

    public function getInlineQueryID(): string
    {
        return $this->inlineQuery()->id();
    }

    public function getInlineMessID()
    {
        return $this->chosenInlineResult()->inlineMessageId();
    }

    // Кастомные методы

    /**
     * @param string $text
     * @param string $keyboard
     * @param bool $reply_mode
     * @param array $options
     * @return Message
     * @throws Exception\TeletantException
     */
    public function reply(string $text, string $keyboard = null, bool $reply_mode = false, array $options = []): Message
    {
        $text = $this->Formatter()->format($text);
        $fields = ['chat_id' => $this->getChatID(), 'text' => $text, 'reply_markup' => (string)$keyboard];
        if ($reply_mode) $fields['reply_to_message_id'] = $this->getMessageID();
        $fields = $fields + $options;
        return $this->api->sendMessage($fields);
    }

    /**
     * @param string $text
     * @param string $keyboard
     * @param bool $reply_mode
     * @param array $options
     * @return Message
     * @throws Exception\TeletantException
     */
    public function replyHTML(string $text, string $keyboard = null, bool $reply_mode = false, array $options = []): Message
    {
        $options['parse_mode'] = 'HTML';
        $text = $this->Formatter()->format($text);
        return $this->reply($text, $keyboard, $reply_mode, $options);
    }

    /**
     * @param string $text
     * @param string $keyboard
     * @param bool $reply_mode
     * @param array $options
     * @return Message
     * @throws Exception\TeletantException
     */
    public function replyMarkdown(string $text, string $keyboard = null, bool $reply_mode = false, array $options = []): Message
    {
        $options['parse_mode'] = 'Markdown';
        $text = $this->Formatter()->format($text);
        return $this->reply($text, $keyboard, $reply_mode, $options);
    }

    /**
     * @param $photo
     * @param string $caption
     * @param string $keyboard
     * @param bool $reply_mode
     * @param array $options
     * @return Message
     * @throws Exception\TeletantException
     */
    public function replyPhoto($photo, string $caption = null, string $keyboard = null, bool $reply_mode = false, array $options = []): Message
    {
        $caption = $this->Formatter()->format($caption);
        $fields = ['chat_id' => $this->getChatID(), 'photo' => $photo, 'caption' => $caption, 'reply_markup' => (string)$keyboard];
        if ($reply_mode) $fields['reply_to_message_id'] = $this->getMessageID();
        $fields = $fields + $options;
        return $this->api->sendPhoto($fields);
    }

    /**
     * @param $document
     * @param string $caption
     * @param string $keyboard
     * @param bool $reply_mode
     * @param array $options
     * @return Message
     * @throws Exception\TeletantException
     */
    public function replyDocument($document, string $caption = null, string $keyboard = null, bool $reply_mode = false, array $options = []): Message
    {
        $caption = $this->Formatter()->format($caption);
        $fields = ['chat_id' => $this->getChatID(), 'document' => $document, 'caption' => $caption, 'reply_markup' => (string)$keyboard];
        if ($reply_mode) $fields['reply_to_message_id'] = $this->getMessageID();
        $fields = $fields + $options;
        return $this->api->sendDocument($fields);
    }

    /**
     * @param $audio
     * @param string $caption
     * @param string $keyboard
     * @param bool $reply_mode
     * @param array $options
     * @return Message
     * @throws Exception\TeletantException
     */
    public function replyAudio($audio, string $caption = null, string $keyboard = null, bool $reply_mode = false, array $options = []): Message
    {
        $caption = $this->Formatter()->format($caption);
        $fields = ['chat_id' => $this->getChatID(), 'audio' => $audio, 'caption' => $caption, 'reply_markup' => (string)$keyboard];
        if ($reply_mode) $fields['reply_to_message_id'] = $this->getMessageID();
        $fields = $fields + $options;
        return $this->api->sendAudio($fields);
    }

    /**
     * @param $video
     * @param string $keyboard
     * @param bool $reply_mode
     * @param array $options
     * @return Message
     * @throws Exception\TeletantException
     */
    public function replyVideo($video, string $keyboard = null, bool $reply_mode = false, array $options = []): Message
    {
        $fields = ['chat_id' => $this->getChatID(), 'video' => $video, 'reply_markup' => (string)$keyboard];
        if ($reply_mode) $fields['reply_to_message_id'] = $this->getMessageID();
        $fields = $fields + $options;
        return $this->api->sendVideo($fields);
    }

    /**
     * @param $media
     * @param bool $reply_mode
     * @param bool $disable_notification
     * @return Messages
     * @throws Exception\TeletantException
     */
    public function replyMediaGroup($media, bool $reply_mode = false, bool $disable_notification = false): Messages
    {
        $fields = ['chat_id' => $this->getChatID(), 'media' => $media, 'disable_notification' => $disable_notification];
        if ($reply_mode) $fields['reply_to_message_id'] = $this->getMessageID();
        return $this->api->sendMediaGroup($fields);
    }

    /**
     * @param string $text
     * @param string $keyboard
     * @param array $options
     * @return Message
     * @throws Exception\TeletantException
     */
    public function editSelf(string $text, string $keyboard = null, array $options = []): Message
    {
        $text = $this->Formatter()->format($text);
        $fields = ['chat_id' => $this->getChatID(), 'message_id' => $this->getMessageID(), 'text' => $text, 'reply_markup' => (string)$keyboard];
        $fields = $fields + $options;
        return $this->api->editMessageText($fields);

    }

    /**
     * @param string $text
     * @param string $keyboard
     * @param bool $disable_web_page_preview
     * @return Message
     * @throws Exception\TeletantException
     */
    public function editSelfHTML(string $text, string $keyboard = null, bool $disable_web_page_preview = false): Message
    {
        $text = $this->Formatter()->format($text);
        return $this->api->editMessageText(['chat_id' => $this->getChatID(), 'message_id' => $this->getMessageID(), 'text' => $text, 'reply_markup' => (string)$keyboard, 'parse_mode' => 'HTML', 'disable_web_page_preview' => $disable_web_page_preview]);
    }

    /**
     * @param string $text
     * @param string $keyboard
     * @param bool $disable_web_page_preview
     * @return Message
     * @throws Exception\TeletantException
     */
    public function editSelfMarkdown(string $text, string $keyboard = null, bool $disable_web_page_preview = false): Message
    {
        $text = $this->Formatter()->format($text);
        return $this->api->editMessageText(['chat_id' => $this->getChatID(), 'message_id' => $this->getMessageID(), 'text' => $text, 'reply_markup' => (string)$keyboard, 'parse_mode' => 'Markdown', 'disable_web_page_preview' => $disable_web_page_preview]);
    }

    /**
     * @param string $text
     * @param bool $alert
     * @param string $url
     * @param int $cache
     * @return TeletantHookResponse|TeletantResponse|\Closure
     * @throws Exception\TeletantException
     */
    public function ansCallback(string $text = null, bool $alert = false, string $url = null, int $cache = 0)
    {
        $text = $this->Formatter()->format($text);
        return $this->api->answerCallbackQuery(['callback_query_id' => $this->getCallbackID(), 'text' => $text, 'show_alert' => $alert, 'url' => $url, 'cache_time' => $cache]);
    }

    /**
     * @param string $id
     * @param string $results
     * @param array $options
     * @return TeletantHookResponse|TeletantResponse|\Closure
     * @throws Exception\TeletantException
     */
    public function ansInlineQuery(string $id, string $results, array $options = [])
    {
        $fields = ['inline_query_id' => $id, 'results' => (string) $results];
        $fields = $fields+$options;
        return $this->api->answerInlineQuery($fields);
    }

    /**
     * @param string $results
     * @param array $options
     * @return TeletantHookResponse|TeletantResponse|\Closure
     * @throws Exception\TeletantException
     */
    public function replyInlineQuery(string $results, array $options = [])
    {
        $fields = ['inline_query_id' => $this->getInlineQueryID(), 'results' => (string) $results];
        $fields = $fields+$options;
        return $this->api->answerInlineQuery($fields);
    }

    /**
     * @param string $fileID
     * @return File
     * @throws Exception\TeletantException
     */
    public function getFile(string $fileID): File
    {
        return $this->api->getFile(['file_id' => $fileID]);
    }

    /**
     * @param string $fileID
     * @return File|string
     * @throws Exception\TeletantException
     */
    public function getFileLink(string $fileID)
    {
        $response = $this->getFile($fileID);
        return ($response->filePath() != null) ? 'https://api.telegram.org/file/bot' . $this->api->getSettings()->getApiToken() . '/' . $response->filePath() : $response;
    }

    /**
     * @param string $action
     * @return TeletantHookResponse|TeletantResponse|\Closure
     * @throws Exception\TeletantException
     */
    public function replyChatAction(string $action)
    {
        return $this->api->sendChatAction(['chat_id' => $this->getChatID(), 'action' => $action]);
    }
}