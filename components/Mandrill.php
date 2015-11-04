<?php
namespace app\components;

use yii\mail\BaseMailer;
use yii\base\InvalidConfigException;
use Mandrill;
use Mandrill_Error;

/**
 * Mandrill is the class that consuming the Message object sends emails thorugh
 * the Mandrill API.
 *
 * @author jayanta kundu
 * @version 1.0
 */

class Mandrill extends BaseMailer
{
    const STATUS_SENT = 'sent';
    const STATUS_QUEUED = 'queued';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_REJECTED = 'rejected';
    const STATUS_INVALID = 'invalid';
    const LOG_CATEGORY = 'mandrill';
    const LANGUAGE_MAILCHIMP = 'mailchimp';
    const LANGUAGE_HANDLEBARS = 'handlebars';
    
    private $to=[];
	private $from;
	private $subject;
	private $body;
	//private $cc;
	//private $bcc;
	private $from_name;
	private $do_not_reply='noreply@gmail.com';
	private $attachment = [];
	//private $sent_status;
	//private $error_code;
	//private $reciever_mail;
    
    /**
     * @var string Mandrill API key
     */
    private $_apikey;

    /**
     * @var Mandrill the Mandrill instance
     */
    private $_mandrill;
    /**
     * Checks that the API key has indeed been set.
     *
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->_apikey) {
            throw new InvalidConfigException('"' . get_class($this) . '::apikey" cannot be null.');
        }
        
        try {
            $this->_mandrill = new Mandrill($this->_apikey);
        } catch (\Exception $exc) {
            \Yii::error($exc->getMessage());
            throw new \Exception('an error occurred with your mailer. Please check the application logs.', 500);
        }
    }
    /**
     * Sets the API key for Mandrill
     *
     * @param string $apikey the Mandrill API key
     * @throws InvalidConfigException
     */
    public function setApikey($apikey)
    {
        if (!is_string($apikey)) {
            throw new InvalidConfigException('"' . get_class($this) . '::apikey" should be a string, "' . gettype($apikey) . '" given.');
        }
        $trimmedApikey = trim($apikey);
        if (!strlen($trimmedApikey) > 0) {
            throw new InvalidConfigException('"' . get_class($this) . '::apikey" length should be greater than 0.');
        }
        $this->_apikey = $trimmedApikey;
    }
	
    /**
     * send Email for Mandrill
     *
     * @param string $body 
     * @throws InvalidConfigException
     */
	public function send($to, $from, $fromname, $do_not_reply, $subject, $body, $attachment='')
	{
		//print_r($attachment); exit;
		$this->to = $to;
		$this->from = $from;
		$this->from_name = $fromname;
		$this->do_not_reply = $do_not_reply;
		$this->subject = $subject;
		$this->body = $body;
        if(!empty($attachment))
			$this->attachment = $attachment;
		else 
			$this->attachment = [];
		//print_r($body); exit;			
		$message = [
					'html' => $this->body,
					'subject' => $this->subject,
					'from_email' => $this->from,
					'from_name' => $this->from_name,
					'to' =>  $this->to,
					'headers' => ['Reply-To' => $this->do_not_reply],
					'attachments'=>$this->attachment,
					
			];
		
        return $this->sendMessage($message);
	}
	
    /**
     * Sends the specified message.
     *
     * @param Message $message the message to be sent
     * @return boolean whether the message is sent successfully
     */
    protected function sendMessage($message)
    {
        try {
                //return  $this->_mandrill->messages->send($message);
                return $this->wasMessageSentSuccesfully($this->_mandrill->messages->send($message));
            
        } catch (Mandrill_Error $e) {
            \Yii::error('A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage(), self::LOG_CATEGORY);
            return false;
        }
    }
    
    /**
     * parse the mandrill response and returns false if any message was either invalid or rejected
     *
     * @param array $mandrillResponse
     * @return boolean
     */
    private function wasMessageSentSuccesfully($mandrillResponse)
    {
        $return = true;
        foreach ($mandrillResponse as $recipient) {
            switch ($recipient['status']) {
                case self::STATUS_INVALID:
                    $return = false;
                    \Yii::warning('the email for "' . $recipient['email'] . '" has not been sent: status "' . $recipient['status'] . '"', self::LOG_CATEGORY);
                    break;
                case self::STATUS_QUEUED:
                    \Yii::info('the email for "' . $recipient['email'] . '" is now in a queue waiting to be sent.', self::LOG_CATEGORY);
                    break;
                case self::STATUS_REJECTED:
                    $return = false;
                    \Yii::warning('the email for "' . $recipient['email'] . '" has been rejected: reason "' . $recipient['reject_reason'] . '"', self::LOG_CATEGORY);
                    break;
                case self::STATUS_SCHEDULED:
                    \Yii::info('the email submission for "' . $recipient['email'] . '" has been scheduled.', self::LOG_CATEGORY);
                    break;
                case self::STATUS_SENT:
                    \Yii::info('the email for "' . $recipient['email'] . '" has been sent.', self::LOG_CATEGORY);
                    break;
            }
        }
        return $return;
    }
    
    
}

?>
