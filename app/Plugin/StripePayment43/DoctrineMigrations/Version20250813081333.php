<?php

declare(strict_types=1);

namespace Plugin\StripePayment43\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250813081333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'StripePayment43: MailTemplate のテンプレートパスを更新';
    }

    public function up(Schema $schema): void
    {
        // 決済失敗通知
        $this->addSql("
            UPDATE dtb_mail_template
               SET file_name = 'StripePayment43/Resource/template/Mail/payment_failure.twig'
             WHERE name = '決済失敗通知'
        ");

        // Stripe返金通知
        $this->addSql("
            UPDATE dtb_mail_template
               SET file_name = 'StripePayment43/Resource/template/Mail/refund.twig'
             WHERE name = 'Stripe返金通知'
        ");

        // Stripe一部キャプチャ通知
        $this->addSql("
            UPDATE dtb_mail_template
               SET file_name = 'StripePayment43/Resource/template/Mail/partial_capture.twig'
             WHERE name = 'Stripe一部キャプチャ通知'
        ");
    }

    public function down(Schema $schema): void {}
}
