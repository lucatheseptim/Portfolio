<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;


class SeedUserTable extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        /*
        $sql= 'INSERT INTO users (name ,email , password)';
        $sql.='values(:name, :email, :password)';
        for($i=0;$i<30;$i++)
        {
            DB::statement($sql,[
                'name'=> Str::random(10),
                'email'=> Str::random(10).'@gmail.com',
                'password'=> Hash::make('password')
            ]);
        }
        */    
        $user = factory(App\User::class ,10)->create();
        
    }
}

