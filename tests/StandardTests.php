<?php

/**
 * Standard test suite for the Sucuri WordPress plugin
 */

class SucuriScannerStandardTests extends \WPAcceptance\PHPUnit\TestCase {
    public function testLogin() {
        parent::_testLogin();
    }

    public function testAdminBarOnFront() {
        parent::_testAdminBarOnFront();
    }

    public function testProfileSave() {
        parent::_testProfileSave();
    }

    public function testInstallPlugin() {
        parent::_testInstallPlugin();
    }

    public function testChangeSiteTitle() {
        parent::_testChangeSiteTitle();
    }

    public function testChangePermalinks() {
        parent::_testChangePermalinks();
    }

    public function testPageLoaded() {
        parent::_testPageLoaded();
    }

    public function testRequiredHTMLTags() {
        parent::_testRequiredHTMLTags();
    }

    public function testPostShows() {
        parent::_testPostShows();
    }
}