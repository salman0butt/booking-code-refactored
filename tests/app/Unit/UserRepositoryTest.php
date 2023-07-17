<?php
use DTApi\Models\User;
use DTApi\Models\UserMeta;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $userRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->userRepository = new UserRepository();
    }


    public function testCreateOrUpdate()
    {
        $requestData = [
            'role' => 'customer',
            'name' => 'John Doe',
            'company_id' => 1,
            'department_id' => 1,
            'email' => 'john.doe@example.com',
            'dob_or_orgid' => '1234567890',
            'phone' => '123456789',
            'mobile' => '987654321',
            'password' => 'password',
            'consumer_type' => 'paid',
            'customer_type' => 'regular',
            'username' => 'johndoe',
            'post_code' => '12345',
            'address' => '123 Street',
            'city' => 'City',
            'town' => 'Town',
            'country' => 'Country',
            'reference' => 'yes',
            'additional_info' => 'Additional info',
            'cost_place' => 'Cost place',
            'fee' => 'Fee',
            'time_to_charge' => 'Time to charge',
            'time_to_pay' => 'Time to pay',
            'charge_ob' => 'Charge ob',
            'customer_id' => '123',
            'charge_km' => 'Charge km',
            'maximum_km' => 'Maximum km',
            'translator_ex' => [1, 2, 3],
            'new_towns' => 'New Town',
            'user_towns_projects' => [1, 2, 3],
            'status' => '1',
        ];

        $model = $this->userRepository->createOrUpdate($id, $requestData);

        $this->assertInstanceOf(User::class, $model);
        $this->assertEquals('customer', $model->user_type);
        $this->assertEquals('John Doe', $model->name);
        $this->assertEquals(1, $model->company_id);
        $this->assertEquals(1, $model->department_id);
        $this->assertEquals('john.doe@example.com', $model->email);
        $this->assertEquals('1234567890', $model->dob_or_orgid);
        $this->assertEquals('123456789', $model->phone);
        $this->assertEquals('987654321', $model->mobile);

        $userMeta = UserMeta::where('user_id', $model->id)->first();
        $this->assertInstanceOf(UserMeta::class, $userMeta);
        $this->assertEquals('paid', $userMeta->consumer_type);
        $this->assertEquals('regular', $userMeta->customer_type);
        $this->assertEquals('johndoe', $userMeta->username);
        $this->assertEquals('12345', $userMeta->post_code);
        $this->assertEquals('123 Street', $userMeta->address);
        $this->assertEquals('City', $userMeta->city);
        $this->assertEquals('Town', $userMeta->town);
        $this->assertEquals('Country', $userMeta->country);
        $this->assertEquals('1', $userMeta->reference);
        $this->assertEquals('Additional info', $userMeta->additional_info);
        $this->assertEquals('Cost place', $userMeta->cost_place);
        $this->assertEquals('Fee', $userMeta->fee);
        $this->assertEquals('Time to charge', $userMeta->time_to_charge);
        $this->assertEquals('Time to pay', $userMeta->time_to_pay);
        $this->assertEquals('Charge ob', $userMeta->charge_ob);
        $this->assertEquals('123', $userMeta->customer_id);
        $this->assertEquals('Charge km', $userMeta->charge_km);
        $this->assertEquals('Maximum km', $userMeta->maximum_km);

    }
}
