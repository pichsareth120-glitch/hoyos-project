import fs from 'fs';
import path from 'path';

function walk(dir) {
    let files = [];
    for (const item of fs.readdirSync(dir)) {
        const full = path.join(dir, item);
        if (fs.statSync(full).isDirectory()) files = files.concat(walk(full));
        else if (full.endsWith('.blade.php')) files.push(full);
    }
    return files;
}

const files = walk('d:/Hoyos-project/resources/views');
const patterns = [
    'text-zinc-500',
    'text-zinc-600',
    'text-gray-500',
    'text-gray-600',
    'text-hoyos-dim',
    'text-white/10',
    'text-white/15',
    'text-white/20',
    'text-white/25',
    'text-white/30',
    'text-white/35',
    'text-white/40',
    'text-white/45'
];

const counts = {};
const groupCounts = {};
patterns.forEach(p => counts[p] = 0);

for (const file of files) {
    const content = fs.readFileSync(file, 'utf8');
    const rel = path.relative('d:/Hoyos-project/resources/views', file).replace(/\\/g, '/');
    const group = rel.split('/')[0];
    if (!groupCounts[group]) groupCounts[group] = {};
    
    for (const p of patterns) {
        // match exact class occurrence
        const escaped = p.replace('/', '\\/');
        const regex = new RegExp(`(?<=[\\s"'` + '`' + `]|^)${escaped}(?=[\\s"'` + '`' + `]|$)`, 'g');
        const m = content.match(regex);
        if (m) {
            counts[p] += m.length;
            groupCounts[group][p] = (groupCounts[group][p] || 0) + m.length;
        }
    }
}

console.log('=== TOTAL COUNTS IN resources/views ===');
console.log(JSON.stringify(counts, null, 2));

console.log('\n=== BY FILE GROUP ===');
console.log(JSON.stringify(groupCounts, null, 2));
