<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateVapidKeys extends Command
{
    protected $signature = 'talenthub:vapid-keys';

    protected $description = 'Generate the key pair used to send phone push notifications';

    public function handle(): int
    {
        $vapidClass = 'Minishlink\\WebPush\\VAPID';
        $keys = $vapidClass::createVapidKeys();

        $this->line('Add these to your .env locally and to Render > Environment:');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->newLine();
        $this->warn('Keep the private key secret. Generate the pair once: changing it later signs everyone out of push.');

        return self::SUCCESS;
    }
}