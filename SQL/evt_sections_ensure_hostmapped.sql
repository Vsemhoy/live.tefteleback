START TRANSACTION;
-- Ensure section ids required by evt_events_import_hostmapped.sql exist.
INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'RJSJ0X4EX0ZBD2D6S138BS3BDX','01KNHVWYBVJT0X6QN30HJ4VDVJ','Fun and joy',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='RJSJ0X4EX0ZBD2D6S138BS3BDX');

INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'RN5Z9R3KVKV4SFZY5M986RPDWY','01KNHVWYBVJT0X6QN30HJ4VDVJ','Health',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='RN5Z9R3KVKV4SFZY5M986RPDWY');

INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT '9JKQ6HZ7E2N6TRCJHRRRCJ18SB','01KNHVWYBVJT0X6QN30HJ4VDVJ','Finance',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='9JKQ6HZ7E2N6TRCJHRRRCJ18SB');

INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'P5S34R7EGV35SMBYJJP7V3F7XW','01KNHVWYBVJT0X6QN30HJ4VDVJ','Home Events',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='P5S34R7EGV35SMBYJJP7V3F7XW');

INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'TQ0XVVCYEKYFG15B9T552AZBDK','01KNHVWYBVJT0X6QN30HJ4VDVJ','Code Informer',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='TQ0XVVCYEKYFG15B9T552AZBDK');

INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'WV9W1QZSFGNH7NS68H3RYHPJZK','01KNHVWYBVJT0X6QN30HJ4VDVJ','Sport',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='WV9W1QZSFGNH7NS68H3RYHPJZK');

INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'TCMMHEKB25D6T1VMREM50T3HJX','01KNHVWYBVJT0X6QN30HJ4VDVJ','Okkio Project',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='TCMMHEKB25D6T1VMREM50T3HJX');

INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT '3VEW507AZD6DQGKQDQHVTAE82G','01KNHVWYBVJT0X6QN30HJ4VDVJ','Shopping',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='3VEW507AZD6DQGKQDQHVTAE82G');

INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'A7FEV7B7CCS68NTNQ1H1SM8W4V','01KNHVWYBVJT0X6QN30HJ4VDVJ','Accidents',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='A7FEV7B7CCS68NTNQ1H1SM8W4V');

INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'C22VRKWR4PA3P7TYBCRX521XGR','01KNHVWYBVJT0X6QN30HJ4VDVJ','Diary notes',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='C22VRKWR4PA3P7TYBCRX521XGR');

INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'ZW3HXWFF3X8Z10ANF02BDY4N72','01KNHVWYBVJT0X6QN30HJ4VDVJ','Payments',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='ZW3HXWFF3X8Z10ANF02BDY4N72');

INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT '82QVM7M962PVZC2ASDV8DQRHK2','01KNHVWYBVJT0X6QN30HJ4VDVJ','Teftele Media',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='82QVM7M962PVZC2ASDV8DQRHK2');

INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'F91RJPF7QR2NS2YTRP8N2P48N0','01KNHVWYBVJT0X6QN30HJ4VDVJ','My Day',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='F91RJPF7QR2NS2YTRP8N2P48N0');

INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'QP4RXKMW69M40QBCRWVNP2PP42','01KNHVWYBVJT0X6QN30HJ4VDVJ','InfoTrash',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='QP4RXKMW69M40QBCRWVNP2PP42');

INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'X06KA2WHPXM08TSHK4TN8AYXR8','01KNHVWYBVJT0X6QN30HJ4VDVJ','Side jobs and tests',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='X06KA2WHPXM08TSHK4TN8AYXR8');

INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'AWY4D9VTDTXZ2KGM66MK6TXGGZ','01KNHVWYBVJT0X6QN30HJ4VDVJ','Teftele Service project',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='AWY4D9VTDTXZ2KGM66MK6TXGGZ');

INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'A59GT3S93MKB77K9N1AQSF1AP1','01KNHVWYBVJT0X6QN30HJ4VDVJ','ARS JOB',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='A59GT3S93MKB77K9N1AQSF1AP1');

INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT 'DY3KE7FYXJGK3Z0XRAXR50BPN2','01KNHVWYBVJT0X6QN30HJ4VDVJ','Study',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='DY3KE7FYXJGK3Z0XRAXR50BPN2');

INSERT INTO evt_sections (id,user_id,name,literals,description,sort_order,access,color,bgcolor,icon,decor,seo,is_archived,is_default,created_at,updated_at)
SELECT '01KNSQP3W8XACC0S9BBJ1818DG','01KNHVWYBVJT0X6QN30HJ4VDVJ','Stuff Story',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM evt_sections WHERE id='01KNSQP3W8XACC0S9BBJ1818DG');

COMMIT;
