<?php

namespace App\Services;

use App\Models\User;
use App\Models\Job;

class ThrottleService {
    public function ignoreThrottle($id)
    {
        $throttle = Throttles::find($id);
        $throttle->ignore = 1;
        $throttle->save();
        return ['success' => 'Changes saved'];
    }

    
    public function userLoginFailed()
    {
        $throttles = Throttles::where('ignore', 0)->with('user')->paginate(15);

        return ['throttles' => $throttles];
    }
}