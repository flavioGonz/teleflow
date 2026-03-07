const fs = require('fs');
let html = fs.readFileSync('ivr-designer.html', 'utf8');

const s1 = '<!-- Left Sidebar: Node Library -->';
const s2 = '<main class="flex-1 relative overflow-hidden grid-bg';
const s3 = '<!-- Right Sidebar: Properties -->';
const s4 = '<!-- Footer / Status Bar -->';

const p1 = html.indexOf(s1);
const p2 = html.indexOf(s2);
const p3 = html.indexOf(s3);
const p4 = html.indexOf(s4);

let headerSection = html.substring(0, p1);
let nodeLibrary = html.substring(p1, p2);
let mainCanvas = html.substring(p2, p3);
let propertiesPanel = html.substring(p3, p4);
let footerSection = html.substring(p4);

headerSection = headerSection.replace('<div class="flex flex-1 overflow-hidden">', '<div class="flex flex-1 overflow-hidden relative">');

nodeLibrary = nodeLibrary.replace('Left Sidebar:', 'Right Sidebar:');
nodeLibrary = nodeLibrary.replace('border-r', 'border-l');
nodeLibrary = nodeLibrary.replace('shadow-xl', 'shadow-[-10px_0_30px_rgba(0,0,0,0.05)]');

propertiesPanel = propertiesPanel.replace('<aside class="w-80 bg-white dark:bg-background-dark border-l border-primary/10 flex flex-col z-20 shadow-[-10px_0_30px_rgba(0,0,0,0.05)]">', 
'<aside id="properties-panel" class="absolute top-0 right-0 h-full w-80 bg-white dark:bg-background-dark border-l border-primary/10 flex flex-col z-50 shadow-2xl translate-x-full transition-transform duration-300">');

let jsInjection = `
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const propsPanel = document.getElementById('properties-panel');
        const nodes = document.querySelectorAll('.node');
        
        nodes.forEach(node => {
            node.addEventListener('click', (e) => {
                e.stopPropagation();
                propsPanel.classList.remove('translate-x-full');
            });
        });

        document.addEventListener('click', () => {
            if (propsPanel) propsPanel.classList.add('translate-x-full');
        });
        
        if (propsPanel) {
            propsPanel.addEventListener('click', (e) => e.stopPropagation());
            
            // Allow closing via close button if added
            const closeBtn = propsPanel.querySelector('.close-props');
            if(closeBtn) {
                closeBtn.addEventListener('click', () => {
                    propsPanel.classList.add('translate-x-full');
                });
            }
        }
    });
</script>
</body>`;

footerSection = footerSection.replace('</body>', jsInjection);

const newHtml = headerSection + mainCanvas + nodeLibrary + propertiesPanel + footerSection;
fs.writeFileSync('ivr-designer.html', newHtml);
console.log('Modified ivr-designer.html');
