# Yii2 Graph API Client

Ini adalah komponen untuk mengirim email menggunakan Microsoft Graph API via [Yii2 Graph Mailer](https://github.com/HumanInitiative/yii2-graph-mailer).

## Instalasi

### 1. Install via Composer

```bash
composer require humaninitiative/yii2-graph-api-client:"dev-master"
```

### 2. Config Aplikasi

Tambahkan pada `.env` :

```
MAILER_API_URL="API_URL"
```

Lalu tambahkan pada `config/web.php` :

```
'components' => [
	'mailer' => [
		'class' => 'humaninitiative\graph\mailer\client\ApiMailer',
		'apiUrl' => $_ENV['MAILER_API_URL'],
	],
],
```

### 4. Contoh Penggunaan

```
Yii::$app->mailer->compose()
        ->setTo('test@test.com')
        ->setSubject('test')
        ->setHtmlBody('<p>test pengiriman email via graph api</p>')
	//->setCc('cc@test.com');
        //->setReplyTo('replyTo@test.com');
        //->attach(Yii::getAlias('@webroot/uploads/file.pdf'));
        ->send();
```
