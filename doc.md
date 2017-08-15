

为了兼容旧的MD5加密方式，此处需要修改vendor/framework/src/IIIuminate/Auth/EloquentUserProvider.php下的代码

`
public function validateCredentials(UserContract $user, array $credentials)
{
    // $plain = $credentials['password'];

    // return $this->hasher->check($plain, $user->getAuthPassword());

     $plain = $credentials['password'];
     $authPassword = $user->getAuthPassword();
     $authSalt = $user->getAuthSalt();

     return $authPassword === md5($plain.$authSalt);
}
`