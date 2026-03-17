import { nextTick, ref } from 'vue';

interface IntroStep {
    element?: string;
    title: string;
    description: string;
    position?: 'top' | 'bottom' | 'left' | 'right';
}

export function usePreviewIntro() {
    const showIntro = ref(false);
    const currentStep = ref(0);
    const isIntroActive = ref(false);

    const steps: IntroStep[] = [
        {
            title: 'Welcome to the Previewer!',
            description: "This quick tour will guide you through the key features of this preview page. Let's get started!",
        },
        {
            element: '#topDetails',
            title: 'Preview Details',
            description: 'Here are some basic information about the preview.',
            position: 'bottom',
        },
        {
            element: '#navbar',
            title: 'Creative Showcase',
            description:
                'This sidebar displays different creatives like Banner, Video, Social Image, Storyboard, and GIF. Click on any creative to view its contents.',
            position: 'right',
        },
        {
            element: '.feedbackTabs-parent',
            title: 'Feedback Tabs',
            description: 'These tabs represent different feedback rounds. Switch between them to view feedback for each category.',
            position: 'bottom',
        },
        {
            element: '.feedbackSetsContainer',
            title: 'Asset Display',
            description: 'All the assets will be loaded here with their respective version and sets.',
            position: 'left',
        },
        {
            element: '#mobilecolorPaletteClick',
            title: 'Color Palette',
            description: 'Click here to change the theme color palette for this preview page.',
            position: 'left',
        },
        {
            element: '#feedbackClick',
            title: 'Feedback Description',
            description: 'Click here to view detailed feedback notes and comments for the current feedback round.',
            position: 'left',
        },
    ];

    const startIntro = () => {
        isIntroActive.value = true;
        currentStep.value = 0;
        nextTick(() => {
            highlightStep(0);
        });
    };

    const nextStep = () => {
        if (currentStep.value < steps.length - 1) {
            currentStep.value++;
            highlightStep(currentStep.value);
        } else {
            endIntro();
        }
    };

    const prevStep = () => {
        if (currentStep.value > 0) {
            currentStep.value--;
            highlightStep(currentStep.value);
        }
    };

    const skipIntro = () => {
        endIntro();
    };

    const endIntro = () => {
        isIntroActive.value = false;
        currentStep.value = 0;
        removeHighlight();

        // Store in localStorage that user has seen the intro
        // Commented out for now - intro will show every time
        // localStorage.setItem('preview_intro_completed', 'true');
    };

    const highlightStep = (stepIndex: number) => {
        removeHighlight();

        const step = steps[stepIndex];

        // If no element is specified (welcome step), just show the modal
        if (!step.element) {
            return;
        }

        const element = document.querySelector(step.element);

        if (element) {
            // Determine scroll position based on element
            const scrollOptions: ScrollIntoViewOptions = {
                behavior: 'smooth',
                block: 'center',
            };

            // For navbar (category list), use 'start' with offset to avoid cutoff
            if (step.element === '#navbar') {
                scrollOptions.block = 'start';

                // Manual scroll with offset for navbar
                const elementRect = element.getBoundingClientRect();
                const absoluteElementTop = elementRect.top + window.pageYOffset;
                const offset = 80; // Add 80px offset from top

                window.scrollTo({
                    top: absoluteElementTop - offset,
                    behavior: 'smooth',
                });
            } else {
                // Scroll element into view smoothly for other elements
                element.scrollIntoView(scrollOptions);
            }

            // Small delay to ensure scroll completes before highlighting
            setTimeout(() => {
                // Add highlight class
                element.classList.add('intro-highlight');

                // Store original position and z-index
                const htmlElement = element as HTMLElement;
                const originalPosition = htmlElement.style.position || getComputedStyle(htmlElement).position;
                const originalZIndex = htmlElement.style.zIndex;

                htmlElement.setAttribute('data-original-position', originalPosition);
                htmlElement.setAttribute('data-original-zindex', originalZIndex);

                // Only set position: relative if element isn't already positioned
                if (originalPosition === 'static' || !originalPosition) {
                    htmlElement.style.position = 'relative';
                }

                // Set higher z-index
                htmlElement.style.zIndex = '10001';

                // Manage fixed positioned buttons based on current step
                const colorPaletteBtn = document.getElementById('mobilecolorPaletteClick');
                const feedbackBtn = document.getElementById('feedbackClick');

                // Only manage buttons that are relevant to the current step
                if (step.element === '#mobilecolorPaletteClick') {
                    // When highlighting color palette, only elevate color palette button
                    if (colorPaletteBtn) {
                        (colorPaletteBtn as HTMLElement).style.zIndex = '10004';
                    }
                } else if (step.element === '#feedbackClick') {
                    // When highlighting feedback description, only elevate feedback button
                    if (feedbackBtn) {
                        (feedbackBtn as HTMLElement).style.zIndex = '10004';
                    }
                } else if (step.element === '.feedbackSetsContainer') {
                    // When focusing on asset display, elevate both buttons so they remain accessible
                    if (colorPaletteBtn) {
                        (colorPaletteBtn as HTMLElement).style.zIndex = '10003';
                    }
                }
                // For other steps (topDetails, navbar, feedbackTabs), don't manage button z-indexes
            }, 300);
        }
    };

    const removeHighlight = () => {
        const highlighted = document.querySelectorAll('.intro-highlight');
        highlighted.forEach((el) => {
            el.classList.remove('intro-highlight');
            const htmlElement = el as HTMLElement;

            // Restore original position and z-index
            const originalPosition = htmlElement.getAttribute('data-original-position');
            const originalZIndex = htmlElement.getAttribute('data-original-zindex');

            if (originalPosition && originalPosition === 'static') {
                htmlElement.style.position = '';
            }
            htmlElement.style.zIndex = originalZIndex || '';

            htmlElement.removeAttribute('data-original-position');
            htmlElement.removeAttribute('data-original-zindex');
        });

        // Reset z-index on fixed buttons
        const colorPaletteBtn = document.getElementById('mobilecolorPaletteClick');
        const feedbackBtn = document.getElementById('feedbackClick');

        if (colorPaletteBtn) {
            (colorPaletteBtn as HTMLElement).style.zIndex = '';
        }
        if (feedbackBtn) {
            (feedbackBtn as HTMLElement).style.zIndex = '';
        }
    };

    const getCurrentStep = () => {
        return steps[currentStep.value];
    };

    const hasCompletedIntro = () => {
        return localStorage.getItem('preview_intro_completed') === 'true';
    };

    const resetIntro = () => {
        localStorage.removeItem('preview_intro_completed');
    };

    return {
        showIntro,
        isIntroActive,
        currentStep,
        steps,
        startIntro,
        nextStep,
        prevStep,
        skipIntro,
        endIntro,
        getCurrentStep,
        hasCompletedIntro,
        resetIntro,
    };
}
