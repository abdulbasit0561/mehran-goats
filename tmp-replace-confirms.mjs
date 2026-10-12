import fs from 'fs';
import path from 'path';

const root = 'C:/laragon/www/mehran-goats';
const skip = new Set(['node_modules', 'vendor', 'storage', 'public', 'bootstrap']);

function walk(dir, acc = []) {
    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        if (skip.has(entry.name)) continue;
        const full = path.join(dir, entry.name);
        if (entry.isDirectory()) walk(full, acc);
        else if (/\.(php|js)$/.test(entry.name)) acc.push(full);
    }
    return acc;
}

const pattern = /(onsubmit|onclick)="return confirm\(\\?'([\s\S]*?)\\?'\)"/g;
let files = 0;
let hits = 0;

for (const file of walk(root)) {
    const source = fs.readFileSync(file, 'utf8');
    if (!source.includes('return confirm(')) continue;
    const next = source.replace(pattern, (_match, _attr, message) => {
        hits += 1;
        const text = message
            .replace(/\\n/g, '\n')
            .replace(/\\'/g, "'")
            .replace(/\n/g, '&#10;')
            .replace(/"/g, '&quot;');
        return `data-confirm="${text}"`;
    });
    if (next === source) {
        console.log('UNCHANGED', path.relative(root, file));
        continue;
    }
    fs.writeFileSync(file, next);
    files += 1;
    console.log(path.relative(root, file));
}

console.log('files', files, 'hits', hits);
