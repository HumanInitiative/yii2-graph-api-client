<?php

namespace humaninitiative\graph\mailer\client;

use Yii;
use yii\base\BaseObject;
use yii\httpclient\Client;
use yii\httpclient\Exception;

class ApiMailerMessage extends BaseObject
{
    private $_mailer;

    public $from;
    public $to = [];
    public $cc = [];
    public $bcc = [];
    public $replyTo = [];
    public $subject;
    public $textBody;
    public $htmlBody;
    public $attachments = [];

    /**
     * Constructor for ApiMailerMessage
     *
     * @param ApiMailer $mailer Instance of ApiMailer
     * @param array $config Configuration for ApiMailerMessage
     */
    public function __construct(ApiMailer $mailer, $config = [])
    {
        $this->_mailer = $mailer;
        parent::__construct($config);
    }

    /**
     * Set the sender of the message
     *
     * @param string $from The sender of the message
     * @return static
     */
    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }
    
    /**
     * Set the recipients of the message
     *
     * @param string|array $to The recipient(s) of the message, can be a string or an array of strings
     * @return static
     */
    public function setTo($to)
    {
        $this->to = (array) $to;
        return $this;
    }

    /**
     * Set the CC of the message
     *
     * @param string|array $cc The recipient(s) of the message, can be a string or an array of strings
     * @return static
     */
    public function setCc($cc)
    {
        $this->cc = (array) $cc;
        return $this;
    }
    
    /**
     * Set the blind carbon copy (BCC) recipients of the message
     *
     * @param string|array $bcc The BCC recipient(s) of the message, can be a string or an array of strings
     * @return static
     */
    public function setBcc($bcc)
    {
        $this->bcc = (array) $bcc;
        return $this;
    }
    
    /**
     * Set the reply-to addresses of the message
     *
     * @param string|array $replyTo The reply-to address(es) of the message, can be a string or an array of strings
     * @return static
     */
    public function setReplyTo($replyTo)
    {
        $this->replyTo = (array) $replyTo;
        return $this;
    }
    
    /**
     * Set the subject of the message
     *
     * @param string $subject The subject of the message
     * @return static
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }
    
    /**
     * Set the text body of the message
     *
     * @param string $text The text body of the message
     * @return static
     */
    public function setTextBody($text)
    {
        $this->textBody = $text;
        return $this;
    }
    
    /**
     * Set the HTML body of the message
     *
     * @param string $html The HTML body of the message
     * @return static
     */
    public function setHtmlBody($html)
    {
        $this->htmlBody = $html;
        return $this;
    }
    
    /**
     * Attach a file to the message
     *
     * @param string $filePath The path of the file to attach
     * @param array $options The options for the attachment
     * @return static
     *
     * Options:
     *   - fileName: The name of the file (default is the basename of the path)
     */
    public function attach($filePath, array $options = [])
    {
        if (!file_exists($filePath)) {
            Yii::error("File lampiran tidak ditemukan: " . $filePath, __METHOD__);
            return $this;
        }

        $this->attachments[] = [
            'path' => $filePath,
            'name' => $options['fileName'] ?? basename($filePath),
        ];
        return $this;
    }

    /**
     * Attach a content to the message
     *
     * @param string $content The content to attach
     * @param string $fileName The name of the file
     * @param array $options The options for the attachment
     * @return static
     *
     * Options:
     *   - contentType: The content type of the file (default is 'application/octet-stream')
     */
    public function attachContent($content, $fileName, array $options = [])
    {
        $this->attachments[] = [
            'content' => $content,
            'name' => $fileName,
            'type' => $options['contentType'] ?? 'application/octet-stream',
        ];
        return $this;
    }

    
    /**
     * Send the email using Microsoft Graph API
     *
     * This method will send the email using Microsoft Graph API. It will validate the input first,
     * then create the email message using method chaining, and finally send the email.
     *
     * @return bool true if the email is sent successfully, false otherwise
     */
    public function send()
    {
        $requestData = [
            'to' => json_encode($this->to),
            'subject' => $this->subject,
            'body' => $this->htmlBody ?? $this->textBody,
        ];

        if (!empty($this->cc)) {
            $requestData['cc'] = json_encode($this->cc);
        }

        if (!empty($this->bcc)) {
            $requestData['bcc'] = json_encode($this->bcc);
        }

        if (!empty($this->replyTo)) {
            $requestData['replyTo'] = json_encode($this->replyTo);
        }

        try {
            $client = new Client();
            
             $request = $client->createRequest()
                ->setMethod('POST')
                ->setUrl($this->_mailer->getApiUrl())
                ->addHeaders([
                    'Content-Type' => 'multipart/form-data',
                    'Accept' => 'application/json',
                ])
                ->setData($requestData);

            foreach ($this->attachments as $attachment) {
                $fileContent = '';
                if (isset($attachment['path'])) {
                    $fileContent = $attachment['path'];
                } elseif (isset($attachment['content'])) {
                    $fileContent = $attachment['content'];
                }

                if ($fileContent) {
                    $request->addFile(
                        'attachments[]',
                        $fileContent,
                        ['fileName' => $attachment['name'], 'mimeType' => $attachment['type'] ?? 'application/octet-stream'],
                    );
                }
            }

            $response = $request->send();

            if ($response->isOk) {
                return true;
            } else {
                Yii::error('Failed to send email via API. Status: ' . $response->statusCode . ' Response: ' . $response->content, __METHOD__);
                return false;
            }
        } catch (Exception $e) {
            Yii::error('Failed to send email via API: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }
}