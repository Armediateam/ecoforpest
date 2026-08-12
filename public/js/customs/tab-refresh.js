document.addEventListener('livewire:load', () => {
    const el = document.querySelector('[wire\\:id]');
    if (!el) {
        console.error('Livewire component element not found');
        return;
    }
    const livewire = Livewire.find(el.getAttribute('wire:id'));
    if (!livewire) {
        console.error('Livewire instance not found');
        return;
    }

    const tabs = document.querySelectorAll('#leadTabs button');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            console.log('Emit tabChanged event');
            livewire.emit('tabChanged');
        });
    });
});
