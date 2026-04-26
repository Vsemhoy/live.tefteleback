-- MySQL Migration Script for Stuffer Tables to ULID

-- stf_locations
ALTER TABLE stf_locations MODIFY id CHAR(26) NOT NULL;
ALTER TABLE stf_locations MODIFY user_id CHAR(26) NOT NULL;
ALTER TABLE stf_locations MODIFY parent_id CHAR(26);

-- stf_things  
ALTER TABLE stf_things MODIFY id CHAR(26) NOT NULL;
ALTER TABLE stf_things MODIFY user_id CHAR(26) NOT NULL;
ALTER TABLE stf_things MODIFY parent_id CHAR(26);
ALTER TABLE stf_things MODIFY category_id CHAR(26);
ALTER TABLE stf_things MODIFY current_location_id CHAR(26);

-- stf_register
ALTER TABLE stf_register MODIFY id CHAR(26) NOT NULL;
ALTER TABLE stf_register MODIFY user_id CHAR(26) NOT NULL;
ALTER TABLE stf_register MODIFY thing_id CHAR(26) NOT NULL;
ALTER TABLE stf_register MODIFY from_location_id CHAR(26);
ALTER TABLE stf_register MODIFY to_location_id CHAR(26);

-- stf_expenses
ALTER TABLE stf_expenses MODIFY id CHAR(26) NOT NULL;
ALTER TABLE stf_expenses MODIFY user_id CHAR(26) NOT NULL;
ALTER TABLE stf_expenses MODIFY thing_id CHAR(26) NOT NULL;
ALTER TABLE stf_expenses MODIFY register_id CHAR(26);
ALTER TABLE stf_expenses MODIFY transaction_id CHAR(26);
