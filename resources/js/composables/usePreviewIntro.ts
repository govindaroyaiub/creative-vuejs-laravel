import { nextTick, ref } from 'vue';

interface IntroStep {
    element?: string;
    mobileElement?: string;
    title: string;
    description: string;
    position?: 'top' | 'bottom' | 'left' | 'right';
    mobileAction?: () => void;
}

export function usePreviewIntro() {
    const showIntro = ref(false);
    const currentStep = ref(0);
    const isIntroActive = ref(false);

    // Store reference to mobile menu open function
    let mobileMenuOpenFn: (() => void) | null = null;
    let mobileMenuCloseFn: (() => void) | null = null;
    let feedbackPanelOpenFn: (() => void) | null = null;
    let feedbackPanelCloseFn: (() => void) | null = null;

    const steps: IntroStep[] = [
        {
            title: 'Hello There!',
            description: "This quick tour will guide you through the key features of the preview page. Let's get started!",
        },
        {
            element: '#topDetails',
            title: 'Basics',
            description: 'Here are some basic information about the preview like Name, Client and Date of creation.',
            position: 'bottom',
        },
        {
            element: '#navbar',
            mobileElement: '#mobileMenuToggle',
            title: 'Showcases',
            description:
                'The sidebar shows creatives like Banner, Video, Social Image, Storyboard, and GIF—click any item to view its contents.',
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
            title: 'Assets',
            description: 'All the assets will be loaded here with their respective version and sets.',
            position: 'left',
        },
        {
            element: '#mobilecolorPaletteClick',
            title: 'Themes',
            description: 'Click here to explore and switch to a modern, vibrant theme that matches your style and preference.',
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
            // Close mobile menu if we're leaving the mobile menu step
            const currentStepData = steps[currentStep.value];
            const isMobile = window.innerWidth <= 1024;
            if (isMobile && currentStepData.mobileElement === '#mobileMenuToggle' && mobileMenuCloseFn) {
                mobileMenuCloseFn();
            }

            // Close feedback panel if we're leaving the feedback description step
            if (isMobile && currentStepData.element === '#feedbackClick' && feedbackPanelCloseFn) {
                feedbackPanelCloseFn();
            }

            currentStep.value++;
            highlightStep(currentStep.value);
        } else {
            endIntro();
        }
    };

    const prevStep = () => {
        if (currentStep.value > 0) {
            // Close mobile menu if we're leaving the mobile menu step
            const currentStepData = steps[currentStep.value];
            const isMobile = window.innerWidth <= 1024;
            if (isMobile && currentStepData.mobileElement === '#mobileMenuToggle' && mobileMenuCloseFn) {
                mobileMenuCloseFn();
            }

            // Close feedback panel if we're leaving the feedback description step
            if (isMobile && currentStepData.element === '#feedbackClick' && feedbackPanelCloseFn) {
                feedbackPanelCloseFn();
            }

            currentStep.value--;
            highlightStep(currentStep.value);
        }
    };

    const skipIntro = () => {
        endIntro();
    };

    const endIntro = () => {
        // Close mobile menu if it's open
        const isMobile = window.innerWidth <= 1024;
        if (isMobile && mobileMenuCloseFn) {
            mobileMenuCloseFn();
        }
        // Close feedback panel if it's open
        if (isMobile && feedbackPanelCloseFn) {
            feedbackPanelCloseFn();
        }

        isIntroActive.value = false;
        currentStep.value = 0;
        removeHighlight();

        // Store in localStorage that user has seen the intro
        localStorage.setItem('preview_intro_completed', 'true');
    };

    const highlightStep = (stepIndex: number) => {
        removeHighlight();

        const step = steps[stepIndex];

        // If no element is specified (welcome step), just show the modal
        if (!step.element && !step.mobileElement) {
            return;
        }

        // Detect if we're on mobile/tablet (width <= 1024px)
        const isMobile = window.innerWidth <= 1024;

        // Choose the appropriate element based on screen size
        const elementSelector = isMobile && step.mobileElement ? step.mobileElement : step.element;

        // Execute mobile-specific action if needed (e.g., open mobile menu)
        if (isMobile && elementSelector === '#mobileMenuToggle' && mobileMenuOpenFn) {
            // Small delay to ensure the tour modal is visible before opening menu
            setTimeout(() => {
                if (mobileMenuOpenFn) {
                    mobileMenuOpenFn();
                }
            }, 500);
        }

        // Open feedback panel on mobile when highlighting feedback button
        if (isMobile && elementSelector === '#feedbackClick' && feedbackPanelOpenFn) {
            setTimeout(() => {
                if (feedbackPanelOpenFn) {
                    feedbackPanelOpenFn();
                }
            }, 500);
        }

        if (!elementSelector) {
            return;
        }

        const element = document.querySelector(elementSelector);

        if (element) {
            // Determine scroll position based on element
            const scrollOptions: ScrollIntoViewOptions = {
                behavior: 'smooth',
                block: 'center',
            };

            // For navbar (category list) on desktop, use 'start' with offset to avoid cutoff
            // For mobile menu toggle, center it
            if (elementSelector === '#navbar') {
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

    const setMobileMenuOpen = (fn: () => void) => {
        mobileMenuOpenFn = fn;
    };

    const setMobileMenuClose = (fn: () => void) => {
        mobileMenuCloseFn = fn;
    };

    const setFeedbackPanelOpen = (fn: () => void) => {
        feedbackPanelOpenFn = fn;
    };

    const setFeedbackPanelClose = (fn: () => void) => {
        feedbackPanelCloseFn = fn;
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
        setMobileMenuOpen,
        setMobileMenuClose,
        setFeedbackPanelOpen,
        setFeedbackPanelClose,
    };
}
