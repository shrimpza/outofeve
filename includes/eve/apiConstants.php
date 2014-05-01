<?php

/**
 *  API access masks
 */
define('CHAR_Locations', 134217728); // Allows the fetching of coordinate and name data for items owned by the character.
define('CHAR_Contracts', 67108864); // List of all Contracts the character is involved in.
define('CHAR_AccountStatus', 33554432); // EVE player account status.
define('CHAR_CharacterInfo_FULL', 16777216); // Sensitive Character Information, exposes account balance and last known location on top of the other Character Information call.
define('CHAR_CharacterInfo', 8388608); // Character information, exposes skill points and current ship information on top of'Show Info'information.
define('CHAR_WalletTransactions', 4194304); // Market transaction journal of character.
define('CHAR_WalletJournal', 2097152); // Wallet journal of character.
define('CHAR_UpcomingCalendarEvents', 1048576); // Upcoming events on characters calendar.
define('CHAR_Standings', 524288); // NPC Standings towards the character.
define('CHAR_SkillQueue', 262144); // Entire skill queue of character.
define('CHAR_SkillInTraining', 131072); // Skill currently in training on the character. Subset of entire Skill Queue.
define('CHAR_Research', 65536); // List of all Research agents working for the character and the progress of the research.
define('CHAR_NotificationTexts', 32768); // Actual body of notifications sent to the character. Requires Notification access to function.
define('CHAR_Notifications', 16384); // List of recent notifications sent to the character.
define('CHAR_Medals', 8192); // Medals awarded to the character.
define('CHAR_MarketOrders', 4096); // List of all Market Orders the character has made.
define('CHAR_MailMessages', 2048); // List of all messages in the characters EVE Mail Inbox.
define('CHAR_MailingLists', 1024); // List of all Mailing Lists the character subscribes to.
define('CHAR_MailBodies', 512); // EVE Mail bodies. Requires MailMessages as well to function.
define('CHAR_KillLog', 256); // Characters kill log.
define('CHAR_IndustryJobs', 128); // Character jobs, completed and active.
define('CHAR_FacWarStats', 64); // Characters Factional Warfare Statistics.
define('CHAR_ContactNotifications', 32); // Most recent contact notifications for the character.
define('CHAR_ContactList', 16); // List of character contacts and relationship levels.
define('CHAR_CharacterSheet', 8); // Character Sheet information. Contains basic'Show Info'information along with clones, account balance, implants, attributes, skills, certificates and corporation roles.
define('CHAR_CalendarEventAttendees', 4); // Event attendee responses. Requires UpcomingCalendarEvents to function.
define('CHAR_AssetList', 2); // Entire asset list of character.
define('CHAR_AccountBalance', 1); // Current balance of characters wallet.

define('CORP_MemberTrackingExtended', 33554432); // Extensive Member information. Time of last logoff, last known location and ship.
define('CORP_Locations', 16777216); // Allows the fetching of coordinate and name data for items owned by the corporation.
define('CORP_Contracts', 8388608); // List of recent Contracts the corporation is involved in.
define('CORP_Titles', 4194304); // Titles of corporation and the roles they grant.
define('CORP_WalletTransactions', 2097152); // Market transactions of all corporate accounts.
define('CORP_WalletJournal', 1048576); // Wallet journal for all corporate accounts.
define('CORP_StarbaseList', 524288); // List of all corporate starbases.
define('CORP_Standings', 262144); // NPC Standings towards corporation.
define('CORP_StarbaseDetail', 131072); // List of all settings of corporate starbases.
define('CORP_Shareholders', 65536); // Shareholders of the corporation.
define('CORP_OutpostServiceDetail', 32768); // List of all service settings of corporate outposts.
define('CORP_OutpostList', 16384); // List of all outposts controlled by the corporation.
define('CORP_Medals', 8192); // List of all medals created by the corporation.
define('CORP_MarketOrders', 4096); // List of all corporate market orders.
define('CORP_MemberTrackingLimited', 2048); // Limited Member information.
define('CORP_MemberSecurityLog', 1024); // Member role and title change log.
define('CORP_MemberSecurity', 512); // Member roles and titles.
define('CORP_KillLog', 256); // Corporation kill log.
define('CORP_IndustryJobs', 128); // Corporation jobs, completed and active.
define('CORP_FacWarStats', 64); // Corporations Factional Warfare Statistics.
define('CORP_ContainerLog', 32); // Corporate secure container acess log.
define('CORP_ContactList', 16); // Corporate contact list and relationships.
define('CORP_CorporationSheet', 8); // Exposes basic'Show Info'information as well as Member Limit and basic division and wallet info.
define('CORP_MemberMedals', 4); // List of medals awarded to corporation members.
define('CORP_AssetList', 2); // List of all corporation assets.
define('CORP_AccountBalance', 1); // Current balance of all corporation accounts.

/**
 * Skill related things
 */
$_attrIntelligence = array(
    array('skill' => 3377, 'bonus' => 1), // Analytical Mind
    array('skill' => 12376, 'bonus' => 1), // Logic
);

$_attrMemory = array(
    array('skill' => 3378, 'bonus' => 1), // Instant Recall
    array('skill' => 12385, 'bonus' => 1), // Eidetic Memory
);

$_attrCharisma = array(
    array('skill' => 3376, 'bonus' => 1), // Empathy
    array('skill' => 12383, 'bonus' => 1), // Presence
);

$_attrPerception = array(
    array('skill' => 3379, 'bonus' => 1), // Spatial Awareness
    array('skill' => 12387, 'bonus' => 1), // Clarity
);

$_attrWillpower = array(
    array('skill' => 3375, 'bonus' => 1), // Iron Will
    array('skill' => 12386, 'bonus' => 1), // Focus
);

$attributeMods = array(
    'intelligence' => $_attrIntelligence,
    'memory' => $_attrMemory,
    'charisma' => $_attrCharisma,
    'perception' => $_attrPerception,
    'willpower' => $_attrWillpower,
);

/**
 * Industry job completion status text
 */
$ramCompletedStatuses = array(
    0 => 'Failed',
    1 => 'Delivered',
    2 => 'Aborted',
    3 => 'GM aborted',
    4 => 'Unanchored',
    5 => 'Destroyed'
);

/**
 * Notification titles
 */
$notificationTitles = array(
    1 => "Legacy",
    2 => "Character deleted",
    3 => "Give medal to character",
    4 => "Alliance maintenance bill",
    5 => "Alliance war declared",
    6 => "Alliance war surrender",
    7 => "Alliance war retracted",
    8 => "Alliance war invalidated by Concord",
    9 => "Bill issued to a character",
    10 => "Bill issued to corporation or alliance",
    11 => "Bill not paid because there's not enough ISK available",
    12 => "Bill, issued by a character, paid",
    13 => "Bill, issued by a corporation or alliance, paid",
    14 => "Bounty claimed",
    15 => "Clone activated",
    16 => "New corp member application",
    17 => "Corp application rejected",
    18 => "Corp application accepted",
    19 => "Corp tax rate changed",
    20 => "Corp news report",
    21 => "Player left corp",
    22 => "Corp news, new CEO",
    23 => "Corp dividend/liquidation, sent to shareholders",
    24 => "Corp dividend payout, sent to shareholders",
    25 => "Corp vote created",
    26 => "Corp CEO votes revoked during voting",
    27 => "Corp declares war",
    28 => "Corp war has started",
    29 => "Corp surrenders war",
    30 => "Corp retracts war",
    31 => "Corp war invalidated by Concord",
    32 => "Container password retrieval",
    33 => "Contraband or low standings cause an attack or items being confiscated",
    34 => "First ship insurance",
    35 => "Ship destroyed, insurance payed",
    36 => "Insurance contract invalidated/runs out",
    37 => "Sovereignty claim failed (alliance)",
    38 => "Sovereignty claim failed (corporation)",
    39 => "Sovereignty bill late (alliance)",
    40 => "Sovereignty bill late (corporation)",
    41 => "Sovereignty claim lost (alliance)",
    42 => "Sovereignty claim lost (corporation)",
    43 => "Sovereignty claim acquired (alliance)",
    44 => "Sovereignty claim acquired (corporation)",
    45 => "Alliance anchoring alert",
    46 => "Alliance structure turns vulnerable",
    47 => "Alliance structure turns invulnerable",
    48 => "Sovereignty disruptor anchored",
    49 => "Structure won/lost",
    50 => "Corp office lease expiration notice",
    51 => "Clone contract revoked by station manager",
    52 => "Corp member clones moved between stations",
    53 => "Clone contract revoked by station manager",
    54 => "Insurance contract expired",
    55 => "Insurance contract issued",
    56 => "Jump clone destroyed",
    57 => "Jump clone destroyed",
    58 => "Corp joining factional warfare",
    59 => "Corp leaving factional warfare",
    60 => "Corp kicked from factional warfare due to low standing",
    61 => "Character kicked from factional warfare due to low standing",
    62 => "Corp in factional warfare warned due to low standing",
    63 => "Character in factional warfare warned due to low standing",
    64 => "Character loses factional warfare rank",
    65 => "Character gains factional warfare rank",
    66 => "Agent has moved",
    67 => "Mass transaction reversal message",
    68 => "Reimbursement message",
    69 => "Agent locates a character",
    70 => "Research mission becomes available from an agent",
    71 => "Agent mission offer expires",
    72 => "Agent mission times out",
    73 => "Agent offers a storyline mission",
    74 => "Tutorial message sent on character creation",
    75 => "Tower alert",
    76 => "Tower resource alert",
    77 => "Station aggression message",
    78 => "Station state change message",
    79 => "Station conquered message",
    80 => "Station aggression message",
    81 => "Corp requests joining factional warfare",
    82 => "Corp requests leaving factional warfare",
    83 => "Corp withdrawing a request to join factional warfare",
    84 => "Corp withdrawing a request to leave factional warfare",
    85 => "Corp liquidation",
    86 => "Territorial Claim Unit under attack",
    87 => "Sovereignty Blockade Unit under attack",
    88 => "Infrastructure Hub under attack",
    89 => "Contact add notification",
    90 => "Contact edit notification",
    91 => "Incursion Completed",
    92 => "Corp Kicked",
    93 => "Customs office has been attacked",
    94 => "Customs office has entered reinforced",
    95 => "Customs office has been transferred",
    96 => "FW Alliance Warning",
    97 => "FW Alliance Kick",
    98 => "AllWarCorpJoined Msg",
    99 => "Ally Joined Defender",
    100 => "Ally Has Joined a War Aggressor",
    101 => "Ally Joined War Ally",
    102 => "New war system: entity is offering assistance in a war.",
    103 => "War Surrender Offer",
    104 => "War Surrender Declined",
    105 => "FacWar LP Payout Kill",
    106 => "FacWar LP Payout Event",
    107 => "FacWar LP Disqualified Eventd",
    108 => "FacWar LP Disqualified Kill",
    109 => "Alliance Contract Cancelled",
    110 => "War Ally Declined Offer",
    111 => "Your Bounty Claimed",
    112 => "Bounty Placed (Char)",
    113 => "Bounty Placed (Corp)",
    114 => "Bounty Placed (Alliance)",
    115 => "Kill Right Available",
    116 => "Kill Right Available Open",
    117 => "Kill Right Earned",
    118 => "Kill Right Used",
    119 => "Kill Right Unavailable",
    120 => "Kill Right Unavailable Open",
    121 => "Declare War",
    122 => "Offered Surrender",
    123 => "Accepted Surrender",
    124 => "Made War Mutual",
    125 => "Retracts War",
    126 => "Offered To Ally",
    127 => "Accepted Ally",
    128 => "Character Application Accepted",
    129 => "Character Application Rejeced",
    130 => "Character Application Withdrawn"
);

/**
 * Corporation roles
 */
$corpRoles = array(
    'corpRoleDirector' => 1,
    'corpRolePersonnelManager' => 128,
    'corpRoleAccountant' => 256,
    'corpRoleSecurityOfficer' => 512,
    'corpRoleFactoryManager' => 1024,
    'corpRoleStationManager' => 2048,
    'corpRoleAuditor' => 4096,
    'corpRoleHangarCanTake1' => 8192,
    'corpRoleHangarCanTake2' => 16384,
    'corpRoleHangarCanTake3' => 32768,
    'corpRoleHangarCanTake4' => 65536,
    'corpRoleHangarCanTake5' => 131072,
    'corpRoleHangarCanTake6' => 262144,
    'corpRoleHangarCanTake7' => 524288,
    'corpRoleHangarCanQuery1' => 1048576,
    'corpRoleHangarCanQuery2' => 2097152,
    'corpRoleHangarCanQuery3' => 4194304,
    'corpRoleHangarCanQuery4' => 8388608,
    'corpRoleHangarCanQuery5' => 16777216,
    'corpRoleHangarCanQuery6' => 33554432,
    'corpRoleHangarCanQuery7' => 67108864,
    'corpRoleAccountCanTake1' => 134217728,
    'corpRoleAccountCanTake2' => 268435456,
    'corpRoleAccountCanTake3' => 536870912,
    'corpRoleAccountCanTake4' => 1073741824,
    'corpRoleAccountCanTake5' => 2147483648,
    'corpRoleAccountCanTake6' => 4294967296,
    'corpRoleAccountCanTake7' => 8589934592,
    'corpRoleAccountCanQuery1' => 17179869184,
    'corpRoleAccountCanQuery2' => 34359738368,
    'corpRoleAccountCanQuery3' => 68719476736,
    'corpRoleAccountCanQuery4' => 137438953472,
    'corpRoleAccountCanQuery5' => 274877906944,
    'corpRoleAccountCanQuery6' => 549755813888,
    'corpRoleAccountCanQuery7' => 1099511627776,
    'corpRoleEquipmentConfig' => 2199023255552,
    'corpRoleContainerCanTake1' => 4398046511104,
    'corpRoleContainerCanTake2' => 8796093022208,
    'corpRoleContainerCanTake3' => 17592186044416,
    'corpRoleContainerCanTake4' => 35184372088832,
    'corpRoleContainerCanTake5' => 70368744177664,
    'corpRoleContainerCanTake6' => 140737488355328,
    'corpRoleContainerCanTake7' => 281474976710656,
    'corpRoleCanRentOffice' => 562949953421312,
    'corpRoleCanRentFactorySlot' => 1125899906842624,
    'corpRoleCanRentResearchSlot' => 2251799813685248,
    'corpRoleJuniorAccountant' => 4503599627370496,
    'corpRoleStarbaseConfig' => 9007199254740992,
    'corpRoleTrader' => 18014398509481984,
    'corpRoleChatManager' => 36028797018963968,
    'corpRoleContractManager' => 72057594037927936,
    'corpRoleInfrastructureTacticalOfficer' => 144115188075855872,
    'corpRoleStarbaseCaretaker' => 288230376151711744,
    'corpRoleFittingManager' => 576460752303423488
);
?>