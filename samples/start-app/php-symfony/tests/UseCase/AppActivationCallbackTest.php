<?php

namespace UseCase;

use App\Entity\Token;
use App\Exception\InvalidStateException;
use App\Exception\AuthorizationCodeException;
use App\Exception\SessionInformationException;
use App\Repository\TokenRepository;
use App\Tests\MockApiTrait;
use App\Tests\Mocks\Oauth2Mock;
use App\UseCase\AppActivationCallback;
use GuzzleHttp\Client;
use \GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AppActivationCallbackTest extends TestCase
{

    use MockApiTrait;

    private AppActivationCallback $appActivationCallback;
    private MockObject $tokenRepository;

    protected function setUp(): void
    {
        $client = new Client(['handler' => $this->mockApi()]);
        $this->tokenRepository = $this->createMock(TokenRepository::class);
        $this->appActivationCallback = new AppActivationCallback(
            $client,
            $this->tokenRepository,
            'oauth_client_id',
            'oauth_client_secret'
        );
    }

    /**
     * @return array
     */
    public function getDataStateException() : array
    {
        return [
            array(
                'state' => ''
            ),
            array(
                'state' => 'badState'
            )
        ];
    }

    /**
     * @test execute() : Empty State Case
     * @dataProvider getDataStateException
     * @return void
     * @throws InvalidStateException
     * @throws AuthorizationCodeException
     * @throws SessionInformationException
     * @throws GuzzleException
     */
    public function testExecuteWithEmptyState($state) : void
    {
        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage(InvalidStateException::INVALID_STATE);

        $session = array(
            'pim_url' => 'http://a_random_pim_url.com',
            'oauth2_state'=> 'state'
        );
        $code = '123';

        $this->appActivationCallback->execute($session, $state, $code);
    }

    /**
     * @test execute() : Empty PIM URL Case
     * @return void
     * @throws InvalidStateException
     * @throws AuthorizationCodeException
     * @throws SessionInformationException
     * @throws GuzzleException
     */
    public function testExecuteWithEmptyAuthCode() : void
    {
        $this->expectException(AuthorizationCodeException::class);
        $this->expectExceptionMessage(AuthorizationCodeException::MISSING_AUTH_CODE);

        $state = 'state';
        $session = array(
            'pim_url' => 'http://a_random_pim_url.com',
            'oauth2_state' => 'state'
        );
        $code = '';

        $this->appActivationCallback->execute($session, $state, $code);
    }

    /**
     * @test execute() : Empty PIM URL Case
     * @return void
     * @throws InvalidStateException
     * @throws AuthorizationCodeException
     * @throws SessionInformationException
     * @throws GuzzleException
     */
    public function testExecuteWithEmptyPimUrl() : void
    {
        $this->expectException(SessionInformationException::class);
        $this->expectExceptionMessage(SessionInformationException::MISSING_PIM_URL);

        $state = 'state';
        $session = array('oauth2_state' => 'state');
        $code = '123';

        $this->appActivationCallback->execute($session, $state, $code);
    }

    /**
     * @return void
     * @throws GuzzleException
     * @throws InvalidStateException
     * @throws AuthorizationCodeException
     * @throws SessionInformationException
     */
    public function testExecute() : void
    {
        $state = 'state';
        $session = array(
            'pim_url' => 'http://a_random_pim_url.com',
            'oauth2_state' => 'state'
        );
        $code = '123';

        $this->tokenRepository->expects($this->once())
            ->method('upsert')
            ->with(Token::create(Oauth2Mock::$response['access_token']), true);

        $this->appActivationCallback->execute($session, $state, $code);
    }
}
