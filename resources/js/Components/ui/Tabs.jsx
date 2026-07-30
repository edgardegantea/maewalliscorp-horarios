import { Tab, TabGroup, TabList, TabPanel, TabPanels } from '@headlessui/react';

export default function Tabs({ tabs, children }) {
    return (
        <TabGroup>
            <TabList className="flex flex-wrap gap-1 border-b border-slate-200 dark:border-slate-800">
                {tabs.map((tab) => (
                    <Tab
                        key={tab}
                        className={({ selected }) =>
                            `-mb-px border-b-2 px-4 py-2.5 text-sm font-medium outline-none transition-colors ${
                                selected
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'
                            }`
                        }
                    >
                        {tab}
                    </Tab>
                ))}
            </TabList>
            <TabPanels className="mt-6">
                {Array.isArray(children) ? children.map((child, i) => <TabPanel key={i}>{child}</TabPanel>) : <TabPanel>{children}</TabPanel>}
            </TabPanels>
        </TabGroup>
    );
}
