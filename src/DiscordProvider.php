<?php

namespace Socialite\Provider;

use Socialite\Two\AbstractProvider;
use Socialite\Two\User;

class DiscordProvider extends AbstractProvider
{
    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'identify',
        'email',
    ];

    /**
     * {@inheritdoc}
     */
    protected $consent = false;

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl(string $state)
    {
        return $this->buildAuthUrlFromBase(
            'https://discord.com/api/oauth2/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = parent::getCodeFields($state);

        if (! $this->consent) {
            $fields['prompt'] = 'none';
        }

        return $fields;
    }

    /**
     * Prompt for consent each time or not.
     *
     * @return $this
     */
    public function withConsent()
    {
        $this->consent = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://discord.com/api/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields(string $code)
    {
        $fields = parent::getTokenFields($code);
        $fields['grant_type'] = 'authorization_code';
        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken(string $token)
    {
        $response = $this->getHttpClient()->get(
            'https://discord.com/api/users/@me', [
            'headers' => [
                'Accept' => 'application/json',
				'Authorization' => 'Bearer '.$token,
                'Client-ID'     => $this->clientId,
            ],
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param  array  $user
     * @return string|null
     *
     * @see https://discord.com/developers/docs/reference#image-formatting-cdn-endpoints
     */
    protected function formatAvatar(array $user)
    {
        if (empty($user['avatar'])) {
            return null;
        }

        $isGif = preg_match('/a_.+/m', $user['avatar']) === 1;
        $extension = $this->getConfig('allow_gif_avatars', true) && $isGif ? 'gif' :
            $this->getConfig('avatar_default_extension', 'jpg');

        return sprintf('https://cdn.discordapp.com/avatars/%s/%s.%s', $user['id'], $user['avatar'], $extension);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'],
            'nickname' => sprintf('%s#%s', $user['username'], $user['discriminator']),
            'name' => $user['username'],
            'email' => $user['email'] ?? null,
            'avatar' => (is_null($user['avatar'])) ? null : sprintf('https://cdn.discordapp.com/avatars/%s/%s.jpg', $user['id'], $user['avatar']),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['allow_gif_avatars', 'avatar_default_extension'];
    }
}
