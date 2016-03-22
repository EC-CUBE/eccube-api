<?php

namespace Plugin\EccubeApi\Repository\OAuth2\OpenID;

use Doctrine\ORM\EntityRepository;
use OAuth2\OpenID\Storage\UserClaimsInterface;

/**
 * UserInfoRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserInfoRepository extends EntityRepository implements UserClaimsInterface
{
    // TODO implements user claims
    public function getUserClaims($user_id, $scope) {
        $UsreInfo =  $this->findOneBy(array('sub' => $user_id));
        // TODO Customer or Member の情報で更新する
        // TODO selected scope
        return $UserInfo->toArray();
    }
}