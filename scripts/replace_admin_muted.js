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

const targets = [
    ...walk('d:/Hoyos-project/resources/views/admin'),
    'd:/Hoyos-project/resources/views/layouts/admin.blade.php',
    ...walk('d:/Hoyos-project/resources/views/components/ui'),
];

let replacedCount = 0;
for (const file of targets) {
    let content = fs.readFileSync(file, 'utf8');
    const orig = content;
    
    // Replace placeholder:text-zinc-500 with placeholder:text-admin-muted
    content = content.replace(/placeholder:text-zinc-500/g, 'placeholder:text-admin-muted');
    
    // Replace text-zinc-500 in lines where it carries information
    const lines = content.split('\n');
    const newLines = lines.map(line => {
        if (!line.includes('text-zinc-500')) return line;
        
        // Skip purely decorative icons or separators
        if (line.includes('<svg') && (line.includes('rotate-180') || line.includes('w-3.5') || line.includes('w-4') || line.includes('pointer-events-none'))) {
            return line;
        }
        if (line.includes('&bull;') || line.includes('>•<') || line.includes('aria-hidden="true">&rarr;') || line.includes('>↗<')) {
            return line;
        }
        
        replacedCount++;
        return line.replace(/text-zinc-500/g, 'text-admin-muted');
    });

    content = newLines.join('\n');
    if (content !== orig) {
        fs.writeFileSync(file, content, 'utf8');
        console.log('Updated: ' + path.relative('d:/Hoyos-project/resources/views', file));
    }
}

console.log('Total text-zinc-500 replaced with text-admin-muted: ' + replacedCount);
