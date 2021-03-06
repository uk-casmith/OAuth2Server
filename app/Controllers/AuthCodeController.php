<?php namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Repositories\RefreshTokenRepository;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\Exception\OAuthServerException;
use Exception;
use App\Helpers\HttpCodes;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use App\Repositories\AuthCodeRepository;
use App\Entities\UserEntity;
use App\Exceptions\AuthCodeException;


class AuthCodeController extends ApiController
{
    /**
     * Authorize an auth code.
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     * @throws AuthCodeException
     */
    public function authorize()
    {
        $authorizationServer = $this->getAuthorizationGrantServer();

        try {
            $authRequest = $authorizationServer->validateAuthorizationRequest($this->request);

            $authRequest->setUser(new UserEntity());

            $authRequest->setAuthorizationApproved(true);

            return $authorizationServer->completeAuthorizationRequest($authRequest, $this->response);
        } catch (OAuthServerException $exception) {
            return $this->respondUnauthorized();
        } catch (Exception $exception) {
            throw new AuthCodeException($exception->getMessage());
        }
    }

    /**
     * Provide a token for an auth code.
     *
     * @return \Psr\Http\Message\StreamInterface|string
     * @throws AuthCodeException
     */
    public function provideToken()
    {
        $authorizationServer = $this->getAuthorizationGrantServer();

        try {
            return $this->respondOk($authorizationServer->respondToAccessTokenRequest(
                $this->request,
                $this->response
            ));
        } catch (OAuthServerException $exception) {
            return $this->respondUnauthorized();
        } catch (Exception $exception) {
            throw new AuthCodeException($exception->getMessage());
        }
    }

    /**
     * Get the authorization server with grant access.
     *
     * @return bool|\League\OAuth2\Server\AuthorizationServer
     */
    protected function getAuthorizationGrantServer() {
        $authorizationServer = $this->getAuthorizationServer();

        $authorizationServer->enableGrantType(
            new AuthCodeGrant(
                new AuthCodeRepository($this->repoConnection),
                new RefreshTokenRepository($this->repoConnection),
                new \DateInterval(getenv('AUTH_CODE_EXPIRE'))
            ),
            new \DateInterval(getenv('ACCESS_TOKEN_EXPIRE'))
        );

        return $authorizationServer;
    }
}