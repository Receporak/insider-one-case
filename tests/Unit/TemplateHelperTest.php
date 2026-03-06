<?php

namespace Tests\Unit;

use App\Helpers\Notification\TemplateHelper;
use Tests\TestCase;

class TemplateHelperTest extends TestCase
{
    private array $mockUser = [
        'name'  => 'Recep Orak',
        'email' => 'recep@insider.com',
        'phone' => '+905551234567',
        'city'  => 'Istanbul',
    ];

    public function test_replaces_single_variable(): void
    {
        $content = 'Merhaba {{name}}, hoş geldiniz!';

        $result = TemplateHelper::render($content, $this->mockUser);

        $this->assertSame('Merhaba Recep Orak, hoş geldiniz!', $result);
    }

    public function test_replaces_multiple_variables(): void
    {
        $content = 'Merhaba {{name}}, e-posta adresiniz: {{email}}';

        $result = TemplateHelper::render($content, $this->mockUser);

        $this->assertSame('Merhaba Recep Orak, e-posta adresiniz: recep@insider.com', $result);
    }

    public function test_unmatched_variable_stays_as_is(): void
    {
        $content = 'Merhaba {{name}}, doğum tarihiniz: {{birthdate}}';

        $result = TemplateHelper::render($content, $this->mockUser);

        $this->assertSame('Merhaba Recep Orak, doğum tarihiniz: {{birthdate}}', $result);
    }

    public function test_no_variables_returns_content_unchanged(): void
    {
        $content = 'Sabit bir bildirim mesajı.';

        $result = TemplateHelper::render($content, $this->mockUser);

        $this->assertSame('Sabit bir bildirim mesajı.', $result);
    }

    public function test_empty_data_leaves_all_variables_intact(): void
    {
        $content = 'Merhaba {{name}}, şehir: {{city}}';

        $result = TemplateHelper::render($content, []);

        $this->assertSame('Merhaba {{name}}, şehir: {{city}}', $result);
    }

    public function test_empty_content_returns_empty_string(): void
    {
        $result = TemplateHelper::render('', $this->mockUser);

        $this->assertSame('', $result);
    }

    public function test_replaces_all_occurrences_of_same_variable(): void
    {
        $content = '{{name}} tarafından gönderildi. Gönderen: {{name}}';

        $result = TemplateHelper::render($content, $this->mockUser);

        $this->assertSame('Recep Orak tarafından gönderildi. Gönderen: Recep Orak', $result);
    }

    public function test_numeric_data_values_are_cast_to_string(): void
    {
        $content = 'Sipariş #{{order_id}} onaylandı.';

        $result = TemplateHelper::render($content, ['order_id' => 42]);

        $this->assertSame('Sipariş #42 onaylandı.', $result);
    }
}
