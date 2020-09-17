<?php

declare(strict_types = 1);

namespace Drupal\authman_github\Plugin\AuthmanOauth;

use Drupal\authman\AuthmanOauth;
use Drupal\authman\Plugin\AuthmanOauthPluginBase;
use Drupal\authman\Plugin\KeyType\OauthClientKeyType;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\key\KeyInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Github provider.
 *
 * @AuthmanOauth(
 *   id = "authman_github",
 *   label = @Translation("GitHub"),
 *   refresh_token = TRUE,
 * )
 *
 * @internal
 */
class AuthmanGithub extends AuthmanOauthPluginBase implements ConfigurableInterface, PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->httpClient = $container->get('http_client');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): void {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance(array $providerOptions, KeyInterface $clientKey): AuthmanOauth {
    $keyType = $clientKey->getKeyType();
    assert($keyType instanceof OauthClientKeyType);
    $provider = $this->createProvider($providerOptions, $clientKey);
    return new AuthmanOauth($provider);
  }

  /**
   * {@inheritdoc}
   */
  protected function createProvider(array $providerOptions, KeyInterface $clientKey): AbstractProvider {
    $values = $clientKey->getKeyValues();
    $provider = new Github([
      'clientId' => $values['client_id'],
      'clientSecret' => $values['client_secret'],
    ] + $providerOptions);
    $provider->setHttpClient($this->httpClient);
    return $provider;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['foo'] = ['#type' => 'textarea'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function renderResourceOwner(ResourceOwnerInterface $resourceOwner): array {
    assert($resourceOwner instanceof GithubResourceOwner);
    $values = $resourceOwner->toArray();
    $build = [];
    $build['owner'] = [
      '#theme' => 'authman_github_resource_owner',
      'name' => $resourceOwner->getName(),
      'username' => $values['login'],
      'url' => $resourceOwner->getUrl(),
      'avatar_url' => $values['avatar_url'],
      'location' => $values['location'],
    ];
    return $build;
  }

}
