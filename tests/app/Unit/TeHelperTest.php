<?php
use Carbon\Carbon;
use Tests\TestCase;
use App\Helpers\TeHelper;

class TeHelperTest extends TestCase
{
    public function testWillExpireAt()
    {
        $dueTime = '2023-07-15 10:00:00';
        $createdAt = '2023-07-14 15:00:00';

        $expectedTime = Carbon::parse('2023-07-15 10:00:00');

        $actualTime = TeHelper::willExpireAt($dueTime, $createdAt);

        $this->assertEquals($expectedTime, $actualTime);
    }
}
