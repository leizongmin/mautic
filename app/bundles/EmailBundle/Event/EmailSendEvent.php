<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class EmailSendEvent.
 */
class EmailSendEvent extends CommonEvent
{
    /**
     * @var MailHelper
     */
    private $helper;

    /**
     * @var Mail
     */
    private $email;

    /**
     * @var string
     */
    private $content = '';

    /**
     * @var string
     */
    private $plainText = '';

    /**
     * @var string
     */
    private $subject = '';

    /**
     * @var string
     */
    private $idHash;

    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var array
     */
    private $source;

    /**
     * @var array
     */
    private $tokens = [];

    /**
     * @var internalSend
     */
    private $internalSend = false;

    /**
     * @var array
     */
    private $textHeaders = [];

    /**
     * @var bool
     */
    private $isDynamicContentParsing;

    /**
     * EmailSendEvent constructor.
     *
     * @param array $args
     * @param bool  $isDynamicContentParsing
     */
    public function __construct(MailHelper $helper = null, $args = [], $isDynamicContentParsing = false)
    {
        $this->helper = $helper;

        if (isset($args['content'])) {
            $this->content = $args['content'];
        }

        if (isset($args['plainText'])) {
            $this->plainText = $args['plainText'];
        }

        if (isset($args['subject'])) {
            $this->subject = $args['subject'];
        }

        if (isset($args['email'])) {
            $this->email = $args['email'];
        }

        if (!$this->subject && isset($args['email']) && $args['email'] instanceof Email) {
            $this->subject = $args['email']->getSubject();
        }

        if (isset($args['idHash'])) {
            $this->idHash = $args['idHash'];
        }

        if (isset($args['lead'])) {
            $this->lead = $args['lead'];
        }

        if (isset($args['source'])) {
            $this->source = $args['source'];
        }

        if (isset($args['tokens'])) {
            $this->tokens = $args['tokens'];
        }

        if (isset($args['internalSend'])) {
            $this->internalSend = $args['internalSend'];
        } elseif (null !== $helper) {
            $this->internalSend = $helper->isInternalSend();
        }

        if (isset($args['textHeaders'])) {
            $this->textHeaders = $args['textHeaders'];
        }

        $this->isDynamicContentParsing = $isDynamicContentParsing;
    }

    /**
     * Check if this email is an internal send or to the lead; if an internal send, don't append lead tracking.
     *
     * @return internalSend
     */
    public function isInternalSend()
    {
        return $this->internalSend;
    }

    /**
     * Return if the transport and mailer is in batch mode (tokenized emails).
     *
     * @return bool
     */
    public function inTokenizationMode()
    {
        return (null !== $this->helper) ? $this->helper->inTokenizationMode() : false;
    }

    /**
     * Returns the Email entity.
     *
     * @return Email
     */
    public function getEmail()
    {
        return (null !== $this->helper) ? $this->helper->getEmail() : $this->email;
    }

    /**
     * Get email content.
     *
     * @param $replaceTokens
     *
     * @return string
     */
    public function getContent($replaceTokens = false)
    {
        if (null !== $this->helper) {
            $content = $this->helper->getBody();
        } else {
            $content = $this->content;
        }

        return ($replaceTokens) ? str_replace(array_keys($this->getTokens()), $this->getTokens(), $content) : $content;
    }

    /**
     * Set email content.
     *
     * @param $content
     */
    public function setContent($content)
    {
        if (null !== $this->helper) {
            $this->helper->setBody($content, 'text/html', null, true);
        } else {
            $this->content = $content;
        }
    }

    /**
     * Get email content.
     *
     * @return array
     */
    public function getPlainText()
    {
        if (null !== $this->helper) {
            return $this->helper->getPlainText();
        } else {
            return $this->plainText;
        }
    }

    /**
     * @param $content
     */
    public function setPlainText($content)
    {
        if (null !== $this->helper) {
            $this->helper->setPlainText($content);
        } else {
            $this->plainText = $content;
        }
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        if (null !== $this->helper) {
            return $this->helper->getSubject();
        } else {
            return $this->subject;
        }
    }

    /**
     * @param string $subject
     *
     * @return EmailSendEvent
     */
    public function setSubject($subject)
    {
        if (null !== $this->helper) {
            $this->helper->setSubject($subject);
        } else {
            $this->subject = $subject;
        }
    }

    /**
     * Get the MailHelper object.
     *
     * @return MailHelper
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * @return array
     */
    public function getLead()
    {
        return (null !== $this->helper) ? $this->helper->getLead() : $this->lead;
    }

    /**
     * @return string
     */
    public function getIdHash()
    {
        return (null !== $this->helper) ? $this->helper->getIdHash() : $this->idHash;
    }

    /**
     * @return array
     */
    public function getSource()
    {
        return (null !== $this->helper) ? $this->helper->getSource() : $this->source;
    }

    public function addTokens(array $tokens)
    {
        $this->tokens = array_merge($this->tokens, $tokens);
    }

    /**
     * @param $key
     * @param $value
     */
    public function addToken($key, $value)
    {
        $this->tokens[$key] = $value;
    }

    /**
     * Get token array.
     *
     * @return array
     */
    public function getTokens($includeGlobal = true)
    {
        $tokens = $this->tokens;

        if ($includeGlobal && null !== $this->helper) {
            $tokens = array_merge($this->helper->getGlobalTokens(), $tokens);
        }

        return $tokens;
    }

    /**
     * @param $name
     * @param $value
     */
    public function addTextHeader($name, $value)
    {
        if (null !== $this->helper) {
            $this->helper->addCustomHeader($name, $value);
        } else {
            $this->textHeaders[$name] = $value;
        }
    }

    /**
     * @return array
     */
    public function getTextHeaders()
    {
        return (null !== $this->helper) ? $this->helper->getCustomHeaders() : $this->textHeaders;
    }

    /**
     * Check if the listener should append it's own clickthrough in URLs or if the email tracking URL conversion process should take care of it.
     *
     * @return bool
     */
    public function shouldAppendClickthrough()
    {
        return !$this->isInternalSend() && null === $this->getEmail();
    }

    /**
     * Generate a clickthrough array for URLs.
     *
     * @return array
     */
    public function generateClickthrough()
    {
        $source       = $this->getSource();
        $email        = $this->getEmail();
        $clickthrough = [
            //what entity is sending the email?
            'source' => $source,
            //the email being sent to be logged in page hit if applicable
            'email' => (null != $email) ? $email->getId() : null,
            'stat'  => $this->getIdHash(),
        ];
        $lead = $this->getLead();
        if (null !== $lead) {
            $clickthrough['lead'] = $lead['id'];
        }

        return $clickthrough;
    }

    /**
     * Get the content hash to note if the content has been changed.
     *
     * @return string
     */
    public function getContentHash()
    {
        if (null !== $this->helper) {
            return $this->helper->getContentHash();
        } else {
            return md5($this->getContent().$this->getPlainText());
        }
    }

    /**
     * @return bool
     */
    public function isDynamicContentParsing()
    {
        return $this->isDynamicContentParsing;
    }
}
