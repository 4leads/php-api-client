<?php

namespace FourLeads;

/**
 * Helper class with constants.
 * Class Contact
 * @package FourLeads
 */
class Contact
{
    //inital status
    const EMAIL_STATUS_UNKONWN = 0;
    //opt-in sent waiting for verification
    const EMAIL_STATUS_PENDING = 1;
    //email address is verified (default status when created by api)
    const EMAIL_STATUS_VERIFIED = 2;
    //email address is temporary blocked (soft bounce)
    const EMAIL_STATUS_BLOCKED = 3;
    //email address has permanent spamdrop status (soft spam report)
    const EMAIL_STATUS_DROPPED = 4;
    //email address bounced (hard bounce)
    const EMAIL_STATUS_BOUNCED = 5;
    //email address is permanent suspended (hard spam report)
    const EMAIL_STATUS_SUSPENDED = 6;
}