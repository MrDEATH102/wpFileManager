<?php
require_once __DIR__ . '/../includes/class-fam-file.php';

class Get_Download_URL_Test extends WP_UnitTestCase {
    public function test_external_url_is_returned_directly() {
        $file = $this->getMockBuilder(FAM_File::class)
            ->onlyMethods(['get'])
            ->getMock();
        $file_obj = (object) ['external_url' => 'https://example.com/file.zip'];
        $file->method('get')->willReturn($file_obj);

        $url = $file->get_download_url(123);
        $this->assertSame('https://example.com/file.zip', $url);
    }

    public function test_internal_file_generates_secure_url() {
        $file = $this->getMockBuilder(FAM_File::class)
            ->onlyMethods(['get'])
            ->getMock();
        $file_obj = (object) [
            'external_url' => null
        ];
        $file->method('get')->willReturn($file_obj);

        $url = $file->get_download_url(55);

        $this->assertStringStartsWith('https://example.org?', $url);
        $this->assertStringContainsString('fam_action=download', $url);
        $this->assertStringContainsString('file_id=55', $url);
        $this->assertStringContainsString('token=nonce-fam_download_55', $url);
    }
}
