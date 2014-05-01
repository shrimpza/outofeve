DROP TABLE IF EXISTS ramCompletedStatuses;
CREATE TABLE ramCompletedStatuses (
  completedStatus int(11) NOT NULL default '0',
  completedStatusText char(100) default NULL,
  description varchar(1000) default NULL,
  PRIMARY KEY  (completedStatus)
);

INSERT INTO ramCompletedStatuses (completedStatus, completedStatusText, description) VALUES(0, 'Failed', '');
INSERT INTO ramCompletedStatuses (completedStatus, completedStatusText, description) VALUES(1, 'Delivered', '');
INSERT INTO ramCompletedStatuses (completedStatus, completedStatusText, description) VALUES(2, 'Aborted', '');
INSERT INTO ramCompletedStatuses (completedStatus, completedStatusText, description) VALUES(3, 'GM aborted', '');
INSERT INTO ramCompletedStatuses (completedStatus, completedStatusText, description) VALUES(4, 'Unanchored', '');
INSERT INTO ramCompletedStatuses (completedStatus, completedStatusText, description) VALUES(5, 'Destroyed', '');

DROP TABLE IF EXISTS crpRoles;
CREATE TABLE crpRoles (
  roleBit int(11) default NULL,
  roleName varchar(100)
);

INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleAccountCanQuery1','17179869184');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleAccountCanQuery2','34359738368');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleAccountCanQuery3','68719476736');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleAccountCanQuery4','137438953472');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleAccountCanQuery5','274877906944');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleAccountCanQuery6','549755813888');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleAccountCanQuery7','1099511627776');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleAccountCanTake1','134217728');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleAccountCanTake2','268435456');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleAccountCanTake3','536870912');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleAccountCanTake4','1073741824');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleAccountCanTake5','2147483648');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleAccountCanTake6','4294967296');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleAccountCanTake7','8589934592');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleAccountant','256');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleAuditor','4096');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleCanRentFactorySlot','1125899906842624');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleCanRentOffice','562949953421312');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleCanRentResearchSlot','2251799813685248');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleChatManager','36028797018963968');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleContainerCanTake1','4398046511104');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleContainerCanTake2','8796093022208');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleContainerCanTake3','17592186044416');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleContainerCanTake4','35184372088832');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleContainerCanTake5','70368744177664');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleContainerCanTake6','140737488355328');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleContainerCanTake7','281474976710656');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleContractManager','72057594037927936');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleStarbaseCaretaker','288230376151711744');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleDirector','1');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleEquipmentConfig','2199023255552');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleFactoryManager','1024');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleHangarCanQuery1','1048576');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleHangarCanQuery2','2097152');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleHangarCanQuery3','4194304');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleHangarCanQuery4','8388608');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleHangarCanQuery5','16777216');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleHangarCanQuery6','33554432');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleHangarCanQuery7','67108864');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleHangarCanTake1','8192');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleHangarCanTake2','16384');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleHangarCanTake3','32768');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleHangarCanTake4','65536');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleHangarCanTake5','131072');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleHangarCanTake6','262144');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleHangarCanTake7','524288');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleJuniorAccountant','4503599627370496');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRolePersonnelManager','128');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleSecurityOfficer','512');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleStarbaseConfig','9007199254740992');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleStationManager','2048');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleTrader','18014398509481984');
INSERT INTO crpRoles (roleName, roleBit) VALUES('corpRoleInfrastructureTacticalOfficer','144115188075855872');
