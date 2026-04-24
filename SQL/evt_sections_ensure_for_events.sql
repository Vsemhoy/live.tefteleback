START TRANSACTION;
-- Ensure all section ids used by evt_events_import exist in evt_sections.
INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'A3Z1AJJB71PXZ1WJJ3HE4VH0ZZ','01KNHVWYBVJT0X6QN30HJ4VDVJ','Fun and joy',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='A3Z1AJJB71PXZ1WJJ3HE4VH0ZZ');

INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT '9FF139S5VMXP71V8AY89MAJP1P','01KNHVWYBVJT0X6QN30HJ4VDVJ','Health',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='9FF139S5VMXP71V8AY89MAJP1P');

INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'HH3T6CHX4M56F4AYESZ4Z5RTQV','01KNHVWYBVJT0X6QN30HJ4VDVJ','Finance',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='HH3T6CHX4M56F4AYESZ4Z5RTQV');

INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'F87VW158TV99TR75V2710KNX5T','01KNHVWYBVJT0X6QN30HJ4VDVJ','Home Events',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='F87VW158TV99TR75V2710KNX5T');

INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT '5JDNJD53RMTXY6SVP1FRP91JKM','01KNHVWYBVJT0X6QN30HJ4VDVJ','Code Informer',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='5JDNJD53RMTXY6SVP1FRP91JKM');

INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'EAN8XFQB4NYC54XS5BYGRASXFD','01KNHVWYBVJT0X6QN30HJ4VDVJ','Sport',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='EAN8XFQB4NYC54XS5BYGRASXFD');

INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT '2AD28H7X0QVK04SFCXCEJCR71W','01KNHVWYBVJT0X6QN30HJ4VDVJ','Okkio Project',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='2AD28H7X0QVK04SFCXCEJCR71W');

INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'CVQ72GEB7ZDNT56MNQHE0ZHQ00','01KNHVWYBVJT0X6QN30HJ4VDVJ','Shopping',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='CVQ72GEB7ZDNT56MNQHE0ZHQ00');

INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'KBEAYWNKH0HBVW015Q4VG80SRR','01KNHVWYBVJT0X6QN30HJ4VDVJ','Accidents',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='KBEAYWNKH0HBVW015Q4VG80SRR');

INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'R7J83H3V41E3NWBW1G7DZEAFTG','01KNHVWYBVJT0X6QN30HJ4VDVJ','Diary notes',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='R7J83H3V41E3NWBW1G7DZEAFTG');

INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'WEX1Q84BGW3SVGT0CN2HS6A320','01KNHVWYBVJT0X6QN30HJ4VDVJ','Payments',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='WEX1Q84BGW3SVGT0CN2HS6A320');

INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'T81QVGAFB8H279P4J4C079ZFDJ','01KNHVWYBVJT0X6QN30HJ4VDVJ','Teftele Media',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='T81QVGAFB8H279P4J4C079ZFDJ');

INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'BKD62D9ESDTPDVE1XZV4Y6GV5D','01KNHVWYBVJT0X6QN30HJ4VDVJ','My Day',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='BKD62D9ESDTPDVE1XZV4Y6GV5D');

INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'CWFQ4G9DSWXCA5CECTBYAX7S60','01KNHVWYBVJT0X6QN30HJ4VDVJ','InfoTrash',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='CWFQ4G9DSWXCA5CECTBYAX7S60');

INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT '37114FY7T4FXBPJB8DN0X8NYYX','01KNHVWYBVJT0X6QN30HJ4VDVJ','Side jobs and tests',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='37114FY7T4FXBPJB8DN0X8NYYX');

INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT '2Y7X6SXMT4BFCWR2TXAMYRW8MB','01KNHVWYBVJT0X6QN30HJ4VDVJ','Teftele Service project',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='2Y7X6SXMT4BFCWR2TXAMYRW8MB');

INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT '2Y910F9N7GHTT3HDPXX98VDRCB','01KNHVWYBVJT0X6QN30HJ4VDVJ','ARS JOB',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='2Y910F9N7GHTT3HDPXX98VDRCB');

INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT '2DVHXGP2TY7SQJ7VFMBN6B6R1C','01KNHVWYBVJT0X6QN30HJ4VDVJ','Study',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='2DVHXGP2TY7SQJ7VFMBN6B6R1C');

INSERT INTO evt_sections (id,user_id,
ame,literals,description,sort_order,ccess,color,gcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT '01KNSQP3W8XACC0S9BBJ1818DG','01KNHVWYBVJT0X6QN30HJ4VDVJ','Stuff Story',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='01KNSQP3W8XACC0S9BBJ1818DG');

COMMIT;
