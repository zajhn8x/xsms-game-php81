<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\XmlParserService;

class XmlParserServiceTest extends TestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(XmlParserService::class);
    }

    public function test_can_parse_xsmb_xml()
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>
        <xsmb>
            <result date="2024-03-20">
                <special>12345</special>
                <prize1>67890</prize1>
            </result>
        </xsmb>';

        $result = $this->service->parseXml($xmlContent);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('draw_date', $result);
        $this->assertArrayHasKey('prizes', $result);
    }
}
