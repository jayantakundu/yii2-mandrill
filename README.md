# Yii2 Mandrill
Yii2 Mandrill
An Mandrill wrapper as Yii2 component.

## Installation
Run Composer to install latest mandrill
```php
composer require mandrill/mandrill
```

Add component to `config/main.php`
```php
'components' => [
// ...
'mandrill' => array (
            'class' => 'app\components\Mandrill',
            'apikey' => 'your mandrill api key',
        ),
// ...        
],        
```
## Usage

## Send email 
```php
$to = array(
	array(
	'email' => $email,
	'name' => $name,
	'type' => 'to'
	)
);
$this->frommail ='sender email';
$this->fromname = 'sender name';
$this->body = 'email body';
$this->donotreply = 'donotreply email';
$this->subject = 'email subject';	

\Yii::$app->mailer->sendMail($to, $this->frommail, $this->fromname, $this->donotreply, $this->subject, $this->body);
```
