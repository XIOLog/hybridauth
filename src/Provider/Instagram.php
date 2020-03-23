<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2020 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Data\Collection;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\User;

/**
 * Instagram OAuth2 provider adapter via Facebook Graph API.
 */
class Instagram extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'user_profile,user_media';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://graph.instagram.com/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://api.instagram.com/oauth/authorize/';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.instagram.com/oauth/access_token/';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://www.instagram.com/developer/authentication/';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        // The Instagram API requires an access_token from authenticated users
        $accessToken = $this->getStoredData($this->accessTokenName);
        $this->apiRequestParameters[$this->accessTokenName] = $accessToken;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $parameters = [
            'fields' => 'id,username,account_type,media_count',
            $this->accessTokenName => $this->getStoredData($this->accessTokenName),
        ];

        $response = $this->apiRequest('me', 'GET', $parameters);

        $data = new Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('username');
        $userProfile->profileURL = "https://instagram.com/{$data->get('username')}";
        $userProfile->data = [
            'account_type' => $data->get('account_type'),
            'media_count' => $data->get('media_count'),
        ];

        return $userProfile;
    }

    /**
     * Fetch user medias.
     *
     * @param int $limit Number of elements per page.
     * @param string $pageId Current pager ID.
     * @param array|null $fields Fields to fetch per media.
     *
     * @throws \Hybridauth\Exception\UnexpectedApiResponseException If API's response is not valid.
     *
     * @return \Hybridauth\Data\Collection Response encapsulated in a `Collection`.
     */
    public function getUserMedia($limit = 12, $pageId = null, array $fields = null)
    {
        if ($fields === null || count($fields) == 0) {
            $fields = [
                'id', 'caption', 'media_type', 'media_url', 'thumbnail_url', 'permalink', 'timestamp', 'username'
            ];
        }

        $params = [
            'fields' => implode(',', $fields),
            'limit' => $limit
        ];
        if ($pageId !== null) {
            $params['after'] = $pageId;
        }

        $response = $this->apiRequest('me/media', 'GET', $params);

        $data = new Collection($response);
        if (!$data->exists('data')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        return $data;
    }

    /**
     * Fetches a single user's media.
     *
     * @param string $mediaId Media ID.
     * @param array|null $fields Fields to fetch per media.
     *
     * @return object Raw response.
     */
    public function getMedia($mediaId, array $fields = null)
    {
        if ($fields === null || count($fields) == 0) {
            $fields = [
                'id', 'caption', 'media_type', 'media_url', 'thumbnail_url', 'permalink', 'timestamp', 'username'
            ];
        }

        $response = $this->apiRequest($mediaId, [
            'fields' => implode(',', $fields)
        ]);

        return $response;
    }
}
