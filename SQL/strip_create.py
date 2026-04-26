#!/usr/bin/env python3
import re

filepath = r'C:\OSPanel646\home\teleback\SQL\26char_DUMP_OLD_ULID.sql'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Remove CREATE TABLE statements
# Remove CREATE TABLE blocks (multiline)
def remove_create_tables(content):
    lines = content.split('\n')
    result = []
    skip = False
    paren_count = 0
    
    for line in lines:
        if 'CREATE TABLE' in line and '(' in line:
            skip = True
            paren_count += line.count('(') - line.count(')')
            continue
        
        if skip:
            paren_count += line.count('(') - line.count(')')
            if paren_count <= 0 and ')' in line:
                skip = False
                paren_count = 0
            continue
        
        result.append(line)
    
    return '\n'.join(result)

content = remove_create_tables(content)

output_path = filepath.replace('.sql', '_NO_CREATE.sql')
with open(output_path, 'w', encoding='utf-8') as f:
    f.write(content)

print(f"Created: {output_path}")