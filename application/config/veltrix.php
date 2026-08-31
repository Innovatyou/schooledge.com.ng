<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Veltrix (shared account) settings
|--------------------------------------------------------------------------
|
| ONE Veltrix customer account serves every school on this platform. All
| SMS/email sent via the "veltrix" gateway option goes out through this
| single account and is billed to it as a whole -- per-school billing is
| handled locally instead (see Wallet_model), never by Veltrix itself.
|
| The API key below is a secret: never expose it to the browser, the mobile
| app, or a school admin. Generate it once on the Veltrix account under
| Developers > API Keys.
|
*/
$config['veltrix_api_base'] = 'https://YOUR-VELTRIX-DOMAIN/api/v1';
$config['veltrix_api_key']  = '';

/*
| Used until a school has its own approved Corporate Sender ID on the
| Veltrix account (sms_credential.sms_api_id = 9, field_one). Must already
| exist and be approved on the Veltrix account.
*/
$config['veltrix_default_sender_id'] = 'SCHLEDGE';

/*
| What SchoolEdge charges a school's wallet per unit sent (NGN). Independent
| of whatever Veltrix charges the master account -- adjust to include a
| margin if desired.
*/
$config['veltrix_sms_price']   = 4.00;
$config['veltrix_email_price'] = 1.00;
