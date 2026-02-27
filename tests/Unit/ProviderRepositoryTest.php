<?php
namespace Fishdaa\CognitoGuard\Tests\Unit;

use Fishdaa\CognitoGuard\Exceptions\MissingRequiredAttributesException;
use Fishdaa\CognitoGuard\ProviderRepository;
use Fishdaa\CognitoGuard\Tests\Fixtures\User;
use Fishdaa\CognitoGuard\Tests\TestCase;
use Fishdaa\CognitoGuard\UserAttributeService;
use Firebase\JWT\SignatureInvalidException;
use Ramsey\Uuid\Uuid;

class ProviderRepositoryTest extends TestCase
{
    /**
     * @var ProviderRepository
     */
    protected $repository;

    /**
     *
     */
    public function setUp(): void {
        parent::setUp();

        $this->repository = new ProviderRepository($this->app['auth']->createUserProvider('users'));
    }

    /**
     * @test
     */
    public function testGetCognitoUser(){
        $cognitoUuid = Uuid::uuid4()->toString();
        $user = User::factory()->create(['cognito_uuid' => $cognitoUuid]);

        $result = $this->repository->getCognitoUser($cognitoUuid);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($cognitoUuid, $result->cognito_uuid);

        $this->assertDatabaseHas('users', [
            'cognito_uuid' => $cognitoUuid,
            'email' => $user->email,
            'name'  => $user->name,
        ]);
    }

    /**
     * @test
     */
    public function testGetCognitoUserNoUser(){
        $cognitoUuid = Uuid::uuid4()->toString();
        User::factory()->create(['cognito_uuid' => $cognitoUuid]);

        $wrongUuid = Uuid::uuid4()->toString();
        $result = $this->repository->getCognitoUser($wrongUuid);
        $this->assertNull($result);
    }
}
