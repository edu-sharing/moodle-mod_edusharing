<?php declare(strict_types = 1);

use EduSharingApiClient\EduSharingAuthHelper;
use EduSharingApiClient\EduSharingHelperBase;
use EduSharingApiClient\EduSharingNodeHelper;
use EduSharingApiClient\EduSharingNodeHelperConfig;
use EduSharingApiClient\UrlHandling;
use mod_edusharing\EduSharingService;
use mod_edusharing\InstallUpgradeLogic;
use mod_edusharing\MetadataLogic;
use mod_edusharing\PluginRegistration;

/**
 * Class InstallUpgradeLogicTest
 *
 * @author Marian Ziegler
 */
class InstallUpgradeLogicTest extends advanced_testcase
{

    /**
     * Function testParseConfigDataThrowsExceptionIfFileNotFound
     *
     * @return void
     * @throws JsonException
     */
    public function testParseConfigDataThrowsExceptionIfFileNotFound(): void {
        $logic = new InstallUpgradeLogic(__DIR__ . '/../nothing/tests/installConfigTest.json');
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing installConfig');
        $logic->parseConfigData();
    }

    /**
     * Function testParseConfigDataThrowsJsonExceptionIfJsonInvalid
     *
     * @return void
     * @throws JsonException
     */
    public function testParseConfigDataThrowsJsonExceptionIfJsonInvalid(): void {
        $logic = new InstallUpgradeLogic(__DIR__ . '/../tests/installConfigTestInvalid.json');
        $this->expectException(JsonException::class);
        $logic->parseConfigData();
    }

    /**
     * Function testPerformReturnsVoidIfAllGoesWell
     *
     * @return void
     * @throws JsonException
     * @throws dml_exception
     */
    public function testPerformReturnsVoidIfAllGoesWell(): void {
        $baseHelper        = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper        = new EduSharingAuthHelper($baseHelper);
        $nodeConfig        = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper        = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $service           = new EduSharingService($authHelper, $nodeHelper);
        $metadataLogicMock = $this->getMockBuilder(MetadataLogic::class)
            ->setConstructorArgs([$service])
            ->getMock();
        $registrationLogicMock = $this->getMockBuilder(PluginRegistration::class)
            ->setConstructorArgs([$service])
            ->getMock();
        $metadataLogicMock->expects($this->once())
            ->method('importMetadata')
            ->with('http://localhost:8080/edu-sharing/metadata?format=lms&external=true');
        $metadataLogicMock->expects($this->once())
            ->method('createXmlMetadata')
            ->will($this->returnValue('superTestData'));
        $registrationLogicMock->expects($this->once())
            ->method('registerPlugin')
            ->will($this->returnValue(['appid' => 'testId']));
        $logic = new InstallUpgradeLogic(__DIR__ . '/../tests/installConfigTest.json');
        $logic->setRegistrationLogic($registrationLogicMock);
        $logic->setMetadataLogic($metadataLogicMock);
        $logic->parseConfigData();
        $logic->perform();
    }
}
