<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { Line, Doughnut, Bar } from 'vue-chartjs';
import { MonitorStop, Video, ImagePlay, Wallpaper, Paperclip, UsersRound, MonitorCog, PiggyBank, Plus, X, Search } from 'lucide-vue-next';
import DotMatrixClock from '@/components/DotMatrixClock.vue';
import axios from 'axios';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    CategoryScale,
    LinearScale,
    PointElement,
    Filler,
    ArcElement,
    BarElement,
    // Add these missing ones
    RadialLinearScale,
    TimeScale,
    TimeSeriesScale,
} from 'chart.js';
import { computed, ref, watchEffect, onMounted, onUnmounted } from 'vue';
import { type BreadcrumbItem } from '@/types';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    LineElement,
    CategoryScale,
    LinearScale,
    PointElement,
    Filler,
    ArcElement,
    BarElement,
    RadialLinearScale,
    TimeScale,
    TimeSeriesScale
);
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];

// Comprehensive timezone database
interface TimezoneData {
    city: string;
    country: string;
    timezone: string;
    region: string;
}

const AVAILABLE_TIMEZONES: TimezoneData[] = [
    { city: 'Tokyo', country: 'Japan', timezone: 'Asia/Tokyo', region: 'Asia' },
    { city: 'Singapore', country: 'Singapore', timezone: 'Asia/Singapore', region: 'Asia' },
    { city: 'Hong Kong', country: 'Hong Kong', timezone: 'Asia/Hong_Kong', region: 'Asia' },
    { city: 'Shanghai', country: 'China', timezone: 'Asia/Shanghai', region: 'Asia' },
    { city: 'Urumqi', country: 'China', timezone: 'Asia/Urumqi', region: 'Asia' },
    { city: 'Seoul', country: 'South Korea', timezone: 'Asia/Seoul', region: 'Asia' },
    { city: 'Dubai', country: 'UAE', timezone: 'Asia/Dubai', region: 'Asia' },
    { city: 'Mumbai', country: 'India', timezone: 'Asia/Kolkata', region: 'Asia' },
    { city: 'Delhi', country: 'India', timezone: 'Asia/Kolkata', region: 'Asia' },
    { city: 'Kolkata', country: 'India', timezone: 'Asia/Kolkata', region: 'Asia' },
    { city: 'Bangkok', country: 'Thailand', timezone: 'Asia/Bangkok', region: 'Asia' },
    { city: 'Jakarta', country: 'Indonesia', timezone: 'Asia/Jakarta', region: 'Asia' },
    { city: 'Makassar', country: 'Indonesia', timezone: 'Asia/Makassar', region: 'Asia' },
    { city: 'Jayapura', country: 'Indonesia', timezone: 'Asia/Jayapura', region: 'Asia' },
    { city: 'Manila', country: 'Philippines', timezone: 'Asia/Manila', region: 'Asia' },
    { city: 'Kuala Lumpur', country: 'Malaysia', timezone: 'Asia/Kuala_Lumpur', region: 'Asia' },
    { city: 'Kuching', country: 'Malaysia', timezone: 'Asia/Kuching', region: 'Asia' },
    { city: 'Dhaka', country: 'Bangladesh', timezone: 'Asia/Dhaka', region: 'Asia' },
    { city: 'Karachi', country: 'Pakistan', timezone: 'Asia/Karachi', region: 'Asia' },
    { city: 'Colombo', country: 'Sri Lanka', timezone: 'Asia/Colombo', region: 'Asia' },
    { city: 'Kathmandu', country: 'Nepal', timezone: 'Asia/Kathmandu', region: 'Asia' },
    { city: 'Thimphu', country: 'Bhutan', timezone: 'Asia/Thimphu', region: 'Asia' },
    { city: 'Yangon', country: 'Myanmar', timezone: 'Asia/Rangoon', region: 'Asia' },
    { city: 'Vientiane', country: 'Laos', timezone: 'Asia/Vientiane', region: 'Asia' },
    { city: 'Phnom Penh', country: 'Cambodia', timezone: 'Asia/Phnom_Penh', region: 'Asia' },
    { city: 'Ho Chi Minh City', country: 'Vietnam', timezone: 'Asia/Ho_Chi_Minh', region: 'Asia' },
    { city: 'Hanoi', country: 'Vietnam', timezone: 'Asia/Ho_Chi_Minh', region: 'Asia' },
    { city: 'Ulaanbaatar', country: 'Mongolia', timezone: 'Asia/Ulaanbaatar', region: 'Asia' },
    { city: 'Hovd', country: 'Mongolia', timezone: 'Asia/Hovd', region: 'Asia' },
    { city: 'Pyongyang', country: 'North Korea', timezone: 'Asia/Pyongyang', region: 'Asia' },
    { city: 'Taipei', country: 'Taiwan', timezone: 'Asia/Taipei', region: 'Asia' },
    { city: 'Macau', country: 'Macau', timezone: 'Asia/Macau', region: 'Asia' },
    { city: 'Bandar Seri Begawan', country: 'Brunei', timezone: 'Asia/Brunei', region: 'Asia' },
    { city: 'Dili', country: 'East Timor', timezone: 'Asia/Dili', region: 'Asia' },
    { city: 'Kabul', country: 'Afghanistan', timezone: 'Asia/Kabul', region: 'Asia' },
    { city: 'Tehran', country: 'Iran', timezone: 'Asia/Tehran', region: 'Asia' },
    { city: 'Baghdad', country: 'Iraq', timezone: 'Asia/Baghdad', region: 'Asia' },
    { city: 'Riyadh', country: 'Saudi Arabia', timezone: 'Asia/Riyadh', region: 'Asia' },
    { city: 'Jeddah', country: 'Saudi Arabia', timezone: 'Asia/Riyadh', region: 'Asia' },
    { city: 'Kuwait City', country: 'Kuwait', timezone: 'Asia/Kuwait', region: 'Asia' },
    { city: 'Doha', country: 'Qatar', timezone: 'Asia/Qatar', region: 'Asia' },
    { city: 'Manama', country: 'Bahrain', timezone: 'Asia/Bahrain', region: 'Asia' },
    { city: 'Muscat', country: 'Oman', timezone: 'Asia/Muscat', region: 'Asia' },
    { city: 'Sanaa', country: 'Yemen', timezone: 'Asia/Aden', region: 'Asia' },
    { city: 'Beirut', country: 'Lebanon', timezone: 'Asia/Beirut', region: 'Asia' },
    { city: 'Damascus', country: 'Syria', timezone: 'Asia/Damascus', region: 'Asia' },
    { city: 'Amman', country: 'Jordan', timezone: 'Asia/Amman', region: 'Asia' },
    { city: 'Jerusalem', country: 'Israel', timezone: 'Asia/Jerusalem', region: 'Asia' },
    { city: 'Tel Aviv', country: 'Israel', timezone: 'Asia/Jerusalem', region: 'Asia' },
    { city: 'Gaza', country: 'Palestine', timezone: 'Asia/Gaza', region: 'Asia' },
    { city: 'Nicosia', country: 'Cyprus', timezone: 'Asia/Nicosia', region: 'Asia' },
    { city: 'Tbilisi', country: 'Georgia', timezone: 'Asia/Tbilisi', region: 'Asia' },
    { city: 'Yerevan', country: 'Armenia', timezone: 'Asia/Yerevan', region: 'Asia' },
    { city: 'Baku', country: 'Azerbaijan', timezone: 'Asia/Baku', region: 'Asia' },
    { city: 'Ashgabat', country: 'Turkmenistan', timezone: 'Asia/Ashgabat', region: 'Asia' },
    { city: 'Tashkent', country: 'Uzbekistan', timezone: 'Asia/Tashkent', region: 'Asia' },
    { city: 'Samarkand', country: 'Uzbekistan', timezone: 'Asia/Samarkand', region: 'Asia' },
    { city: 'Bishkek', country: 'Kyrgyzstan', timezone: 'Asia/Bishkek', region: 'Asia' },
    { city: 'Dushanbe', country: 'Tajikistan', timezone: 'Asia/Dushanbe', region: 'Asia' },
    { city: 'Almaty', country: 'Kazakhstan', timezone: 'Asia/Almaty', region: 'Asia' },
    { city: 'Nur-Sultan', country: 'Kazakhstan', timezone: 'Asia/Almaty', region: 'Asia' },
    { city: 'Aktau', country: 'Kazakhstan', timezone: 'Asia/Aqtau', region: 'Asia' },
    { city: 'Oral', country: 'Kazakhstan', timezone: 'Asia/Oral', region: 'Asia' },

    // Europe
    { city: 'London', country: 'United Kingdom', timezone: 'Europe/London', region: 'Europe' },
    { city: 'Edinburgh', country: 'United Kingdom', timezone: 'Europe/London', region: 'Europe' },
    { city: 'Dublin', country: 'Ireland', timezone: 'Europe/Dublin', region: 'Europe' },
    { city: 'Lisbon', country: 'Portugal', timezone: 'Europe/Lisbon', region: 'Europe' },
    { city: 'Azores', country: 'Portugal', timezone: 'Atlantic/Azores', region: 'Europe' },
    { city: 'Paris', country: 'France', timezone: 'Europe/Paris', region: 'Europe' },
    { city: 'Berlin', country: 'Germany', timezone: 'Europe/Berlin', region: 'Europe' },
    { city: 'Rome', country: 'Italy', timezone: 'Europe/Rome', region: 'Europe' },
    { city: 'Madrid', country: 'Spain', timezone: 'Europe/Madrid', region: 'Europe' },
    { city: 'Canary Islands', country: 'Spain', timezone: 'Atlantic/Canary', region: 'Europe' },
    { city: 'Amsterdam', country: 'Netherlands', timezone: 'Europe/Amsterdam', region: 'Europe' },
    { city: 'Brussels', country: 'Belgium', timezone: 'Europe/Brussels', region: 'Europe' },
    { city: 'Zurich', country: 'Switzerland', timezone: 'Europe/Zurich', region: 'Europe' },
    { city: 'Vienna', country: 'Austria', timezone: 'Europe/Vienna', region: 'Europe' },
    { city: 'Stockholm', country: 'Sweden', timezone: 'Europe/Stockholm', region: 'Europe' },
    { city: 'Oslo', country: 'Norway', timezone: 'Europe/Oslo', region: 'Europe' },
    { city: 'Copenhagen', country: 'Denmark', timezone: 'Europe/Copenhagen', region: 'Europe' },
    { city: 'Helsinki', country: 'Finland', timezone: 'Europe/Helsinki', region: 'Europe' },
    { city: 'Warsaw', country: 'Poland', timezone: 'Europe/Warsaw', region: 'Europe' },
    { city: 'Prague', country: 'Czech Republic', timezone: 'Europe/Prague', region: 'Europe' },
    { city: 'Budapest', country: 'Hungary', timezone: 'Europe/Budapest', region: 'Europe' },
    { city: 'Bratislava', country: 'Slovakia', timezone: 'Europe/Bratislava', region: 'Europe' },
    { city: 'Ljubljana', country: 'Slovenia', timezone: 'Europe/Ljubljana', region: 'Europe' },
    { city: 'Zagreb', country: 'Croatia', timezone: 'Europe/Zagreb', region: 'Europe' },
    { city: 'Sarajevo', country: 'Bosnia & Herzegovina', timezone: 'Europe/Sarajevo', region: 'Europe' },
    { city: 'Belgrade', country: 'Serbia', timezone: 'Europe/Belgrade', region: 'Europe' },
    { city: 'Skopje', country: 'North Macedonia', timezone: 'Europe/Skopje', region: 'Europe' },
    { city: 'Podgorica', country: 'Montenegro', timezone: 'Europe/Podgorica', region: 'Europe' },
    { city: 'Tirana', country: 'Albania', timezone: 'Europe/Tirane', region: 'Europe' },
    { city: 'Athens', country: 'Greece', timezone: 'Europe/Athens', region: 'Europe' },
    { city: 'Bucharest', country: 'Romania', timezone: 'Europe/Bucharest', region: 'Europe' },
    { city: 'Sofia', country: 'Bulgaria', timezone: 'Europe/Sofia', region: 'Europe' },
    { city: 'Istanbul', country: 'Turkey', timezone: 'Europe/Istanbul', region: 'Europe' },
    { city: 'Moscow', country: 'Russia', timezone: 'Europe/Moscow', region: 'Europe' },
    { city: 'Saint Petersburg', country: 'Russia', timezone: 'Europe/Moscow', region: 'Europe' },
    { city: 'Kaliningrad', country: 'Russia', timezone: 'Europe/Kaliningrad', region: 'Europe' },
    { city: 'Samara', country: 'Russia', timezone: 'Europe/Samara', region: 'Europe' },
    { city: 'Volgograd', country: 'Russia', timezone: 'Europe/Volgograd', region: 'Europe' },
    { city: 'Kyiv', country: 'Ukraine', timezone: 'Europe/Kiev', region: 'Europe' },
    { city: 'Simferopol', country: 'Ukraine', timezone: 'Europe/Simferopol', region: 'Europe' },
    { city: 'Minsk', country: 'Belarus', timezone: 'Europe/Minsk', region: 'Europe' },
    { city: 'Vilnius', country: 'Lithuania', timezone: 'Europe/Vilnius', region: 'Europe' },
    { city: 'Riga', country: 'Latvia', timezone: 'Europe/Riga', region: 'Europe' },
    { city: 'Tallinn', country: 'Estonia', timezone: 'Europe/Tallinn', region: 'Europe' },
    { city: 'Chisinau', country: 'Moldova', timezone: 'Europe/Chisinau', region: 'Europe' },
    { city: 'Luxembourg', country: 'Luxembourg', timezone: 'Europe/Luxembourg', region: 'Europe' },
    { city: 'Monaco', country: 'Monaco', timezone: 'Europe/Monaco', region: 'Europe' },
    { city: 'Vaduz', country: 'Liechtenstein', timezone: 'Europe/Vaduz', region: 'Europe' },
    { city: 'Reykjavik', country: 'Iceland', timezone: 'Atlantic/Reykjavik', region: 'Europe' },
    { city: 'Valletta', country: 'Malta', timezone: 'Europe/Malta', region: 'Europe' },
    { city: 'Andorra la Vella', country: 'Andorra', timezone: 'Europe/Andorra', region: 'Europe' },
    { city: 'San Marino', country: 'San Marino', timezone: 'Europe/San_Marino', region: 'Europe' },
    { city: 'Vatican City', country: 'Vatican', timezone: 'Europe/Vatican', region: 'Europe' },
    { city: 'Gibraltar', country: 'Gibraltar', timezone: 'Europe/Gibraltar', region: 'Europe' },
    { city: 'Pristina', country: 'Kosovo', timezone: 'Europe/Belgrade', region: 'Europe' },

    // Americas
    { city: 'New York', country: 'USA', timezone: 'America/New_York', region: 'Americas' },
    { city: 'Chicago', country: 'USA', timezone: 'America/Chicago', region: 'Americas' },
    { city: 'Denver', country: 'USA', timezone: 'America/Denver', region: 'Americas' },
    { city: 'Los Angeles', country: 'USA', timezone: 'America/Los_Angeles', region: 'Americas' },
    { city: 'Anchorage', country: 'USA', timezone: 'America/Anchorage', region: 'Americas' },
    { city: 'Honolulu', country: 'USA', timezone: 'Pacific/Honolulu', region: 'Americas' },
    { city: 'Phoenix', country: 'USA', timezone: 'America/Phoenix', region: 'Americas' },
    { city: 'Adak', country: 'USA', timezone: 'America/Adak', region: 'Americas' },
    { city: 'Indianapolis', country: 'USA', timezone: 'America/Indiana/Indianapolis', region: 'Americas' },
    { city: 'Toronto', country: 'Canada', timezone: 'America/Toronto', region: 'Americas' },
    { city: 'Vancouver', country: 'Canada', timezone: 'America/Vancouver', region: 'Americas' },
    { city: 'Winnipeg', country: 'Canada', timezone: 'America/Winnipeg', region: 'Americas' },
    { city: 'Edmonton', country: 'Canada', timezone: 'America/Edmonton', region: 'Americas' },
    { city: 'Halifax', country: 'Canada', timezone: 'America/Halifax', region: 'Americas' },
    { city: 'St. Johns', country: 'Canada', timezone: 'America/St_Johns', region: 'Americas' },
    { city: 'Regina', country: 'Canada', timezone: 'America/Regina', region: 'Americas' },
    { city: 'Whitehorse', country: 'Canada', timezone: 'America/Whitehorse', region: 'Americas' },
    { city: 'Mexico City', country: 'Mexico', timezone: 'America/Mexico_City', region: 'Americas' },
    { city: 'Tijuana', country: 'Mexico', timezone: 'America/Tijuana', region: 'Americas' },
    { city: 'Hermosillo', country: 'Mexico', timezone: 'America/Hermosillo', region: 'Americas' },
    { city: 'Chihuahua', country: 'Mexico', timezone: 'America/Chihuahua', region: 'Americas' },
    { city: 'Cancun', country: 'Mexico', timezone: 'America/Cancun', region: 'Americas' },
    { city: 'São Paulo', country: 'Brazil', timezone: 'America/Sao_Paulo', region: 'Americas' },
    { city: 'Manaus', country: 'Brazil', timezone: 'America/Manaus', region: 'Americas' },
    { city: 'Belem', country: 'Brazil', timezone: 'America/Belem', region: 'Americas' },
    { city: 'Fortaleza', country: 'Brazil', timezone: 'America/Fortaleza', region: 'Americas' },
    { city: 'Cuiaba', country: 'Brazil', timezone: 'America/Cuiaba', region: 'Americas' },
    { city: 'Rio Branco', country: 'Brazil', timezone: 'America/Rio_Branco', region: 'Americas' },
    { city: 'Noronha', country: 'Brazil', timezone: 'America/Noronha', region: 'Americas' },
    { city: 'Buenos Aires', country: 'Argentina', timezone: 'America/Argentina/Buenos_Aires', region: 'Americas' },
    { city: 'Cordoba', country: 'Argentina', timezone: 'America/Argentina/Cordoba', region: 'Americas' },
    { city: 'Mendoza', country: 'Argentina', timezone: 'America/Argentina/Mendoza', region: 'Americas' },
    { city: 'Lima', country: 'Peru', timezone: 'America/Lima', region: 'Americas' },
    { city: 'Santiago', country: 'Chile', timezone: 'America/Santiago', region: 'Americas' },
    { city: 'Easter Island', country: 'Chile', timezone: 'Pacific/Easter', region: 'Americas' },
    { city: 'Bogota', country: 'Colombia', timezone: 'America/Bogota', region: 'Americas' },
    { city: 'Caracas', country: 'Venezuela', timezone: 'America/Caracas', region: 'Americas' },
    { city: 'Guayaquil', country: 'Ecuador', timezone: 'America/Guayaquil', region: 'Americas' },
    { city: 'Galapagos', country: 'Ecuador', timezone: 'Pacific/Galapagos', region: 'Americas' },
    { city: 'La Paz', country: 'Bolivia', timezone: 'America/La_Paz', region: 'Americas' },
    { city: 'Asuncion', country: 'Paraguay', timezone: 'America/Asuncion', region: 'Americas' },
    { city: 'Montevideo', country: 'Uruguay', timezone: 'America/Montevideo', region: 'Americas' },
    { city: 'Georgetown', country: 'Guyana', timezone: 'America/Guyana', region: 'Americas' },
    { city: 'Paramaribo', country: 'Suriname', timezone: 'America/Paramaribo', region: 'Americas' },
    { city: 'Cayenne', country: 'French Guiana', timezone: 'America/Cayenne', region: 'Americas' },
    { city: 'Havana', country: 'Cuba', timezone: 'America/Havana', region: 'Americas' },
    { city: 'Kingston', country: 'Jamaica', timezone: 'America/Jamaica', region: 'Americas' },
    { city: 'Santo Domingo', country: 'Dominican Republic', timezone: 'America/Santo_Domingo', region: 'Americas' },
    { city: 'Port-au-Prince', country: 'Haiti', timezone: 'America/Port-au-Prince', region: 'Americas' },
    { city: 'San Juan', country: 'Puerto Rico', timezone: 'America/Puerto_Rico', region: 'Americas' },
    { city: 'Nassau', country: 'Bahamas', timezone: 'America/Nassau', region: 'Americas' },
    { city: 'Guatemala City', country: 'Guatemala', timezone: 'America/Guatemala', region: 'Americas' },
    { city: 'Tegucigalpa', country: 'Honduras', timezone: 'America/Tegucigalpa', region: 'Americas' },
    { city: 'San Salvador', country: 'El Salvador', timezone: 'America/El_Salvador', region: 'Americas' },
    { city: 'Managua', country: 'Nicaragua', timezone: 'America/Managua', region: 'Americas' },
    { city: 'San Jose', country: 'Costa Rica', timezone: 'America/Costa_Rica', region: 'Americas' },
    { city: 'Panama City', country: 'Panama', timezone: 'America/Panama', region: 'Americas' },
    { city: 'Belize City', country: 'Belize', timezone: 'America/Belize', region: 'Americas' },
    { city: 'Port of Spain', country: 'Trinidad & Tobago', timezone: 'America/Port_of_Spain', region: 'Americas' },
    { city: 'Bridgetown', country: 'Barbados', timezone: 'America/Barbados', region: 'Americas' },
    { city: 'Willemstad', country: 'Curacao', timezone: 'America/Curacao', region: 'Americas' },
    { city: 'Oranjestad', country: 'Aruba', timezone: 'America/Aruba', region: 'Americas' },

    // Oceania
    { city: 'Sydney', country: 'Australia', timezone: 'Australia/Sydney', region: 'Oceania' },
    { city: 'Melbourne', country: 'Australia', timezone: 'Australia/Melbourne', region: 'Oceania' },
    { city: 'Brisbane', country: 'Australia', timezone: 'Australia/Brisbane', region: 'Oceania' },
    { city: 'Perth', country: 'Australia', timezone: 'Australia/Perth', region: 'Oceania' },
    { city: 'Adelaide', country: 'Australia', timezone: 'Australia/Adelaide', region: 'Oceania' },
    { city: 'Darwin', country: 'Australia', timezone: 'Australia/Darwin', region: 'Oceania' },
    { city: 'Hobart', country: 'Australia', timezone: 'Australia/Hobart', region: 'Oceania' },
    { city: 'Canberra', country: 'Australia', timezone: 'Australia/Canberra', region: 'Oceania' },
    { city: 'Lord Howe Island', country: 'Australia', timezone: 'Australia/Lord_Howe', region: 'Oceania' },
    { city: 'Auckland', country: 'New Zealand', timezone: 'Pacific/Auckland', region: 'Oceania' },
    { city: 'Chatham Islands', country: 'New Zealand', timezone: 'Pacific/Chatham', region: 'Oceania' },
    { city: 'Suva', country: 'Fiji', timezone: 'Pacific/Fiji', region: 'Oceania' },
    { city: 'Port Moresby', country: 'Papua New Guinea', timezone: 'Pacific/Port_Moresby', region: 'Oceania' },
    { city: 'Bougainville', country: 'Papua New Guinea', timezone: 'Pacific/Bougainville', region: 'Oceania' },
    { city: 'Honiara', country: 'Solomon Islands', timezone: 'Pacific/Guadalcanal', region: 'Oceania' },
    { city: 'Port Vila', country: 'Vanuatu', timezone: 'Pacific/Efate', region: 'Oceania' },
    { city: 'Noumea', country: 'New Caledonia', timezone: 'Pacific/Noumea', region: 'Oceania' },
    { city: 'Nuku alofa', country: 'Tonga', timezone: 'Pacific/Tongatapu', region: 'Oceania' },
    { city: 'Apia', country: 'Samoa', timezone: 'Pacific/Apia', region: 'Oceania' },
    { city: 'Pago Pago', country: 'American Samoa', timezone: 'Pacific/Pago_Pago', region: 'Oceania' },
    { city: 'Funafuti', country: 'Tuvalu', timezone: 'Pacific/Funafuti', region: 'Oceania' },
    { city: 'Tarawa', country: 'Kiribati', timezone: 'Pacific/Tarawa', region: 'Oceania' },
    { city: 'Christmas Island', country: 'Kiribati', timezone: 'Pacific/Kiritimati', region: 'Oceania' },
    { city: 'Yaren', country: 'Nauru', timezone: 'Pacific/Nauru', region: 'Oceania' },
    { city: 'Majuro', country: 'Marshall Islands', timezone: 'Pacific/Majuro', region: 'Oceania' },
    { city: 'Palikir', country: 'Micronesia', timezone: 'Pacific/Pohnpei', region: 'Oceania' },
    { city: 'Chuuk', country: 'Micronesia', timezone: 'Pacific/Truk', region: 'Oceania' },
    { city: 'Koror', country: 'Palau', timezone: 'Pacific/Palau', region: 'Oceania' },
    { city: 'Hagatna', country: 'Guam', timezone: 'Pacific/Guam', region: 'Oceania' },
    { city: 'Saipan', country: 'Northern Mariana Islands', timezone: 'Pacific/Saipan', region: 'Oceania' },
    { city: 'Tahiti', country: 'French Polynesia', timezone: 'Pacific/Tahiti', region: 'Oceania' },
    { city: 'Marquesas Islands', country: 'French Polynesia', timezone: 'Pacific/Marquesas', region: 'Oceania' },
    { city: 'Gambier Islands', country: 'French Polynesia', timezone: 'Pacific/Gambier', region: 'Oceania' },
    { city: 'Midway', country: 'US Minor Outlying Islands', timezone: 'Pacific/Midway', region: 'Oceania' },
    { city: 'Wake Island', country: 'US Minor Outlying Islands', timezone: 'Pacific/Wake', region: 'Oceania' },
    { city: 'Fakaofo', country: 'Tokelau', timezone: 'Pacific/Fakaofo', region: 'Oceania' },
    { city: 'Alofi', country: 'Niue', timezone: 'Pacific/Niue', region: 'Oceania' },
    { city: 'Avarua', country: 'Cook Islands', timezone: 'Pacific/Rarotonga', region: 'Oceania' },
    { city: 'Norfolk Island', country: 'Norfolk Island', timezone: 'Pacific/Norfolk', region: 'Oceania' },
    { city: 'Christmas Island', country: 'Australia (territory)', timezone: 'Indian/Christmas', region: 'Oceania' },
    { city: 'Cocos Islands', country: 'Australia (territory)', timezone: 'Indian/Cocos', region: 'Oceania' },

    // Africa
    { city: 'Cairo', country: 'Egypt', timezone: 'Africa/Cairo', region: 'Africa' },
    { city: 'Lagos', country: 'Nigeria', timezone: 'Africa/Lagos', region: 'Africa' },
    { city: 'Abuja', country: 'Nigeria', timezone: 'Africa/Lagos', region: 'Africa' },
    { city: 'Johannesburg', country: 'South Africa', timezone: 'Africa/Johannesburg', region: 'Africa' },
    { city: 'Cape Town', country: 'South Africa', timezone: 'Africa/Johannesburg', region: 'Africa' },
    { city: 'Nairobi', country: 'Kenya', timezone: 'Africa/Nairobi', region: 'Africa' },
    { city: 'Casablanca', country: 'Morocco', timezone: 'Africa/Casablanca', region: 'Africa' },
    { city: 'Algiers', country: 'Algeria', timezone: 'Africa/Algiers', region: 'Africa' },
    { city: 'Tunis', country: 'Tunisia', timezone: 'Africa/Tunis', region: 'Africa' },
    { city: 'Tripoli', country: 'Libya', timezone: 'Africa/Tripoli', region: 'Africa' },
    { city: 'Khartoum', country: 'Sudan', timezone: 'Africa/Khartoum', region: 'Africa' },
    { city: 'Juba', country: 'South Sudan', timezone: 'Africa/Juba', region: 'Africa' },
    { city: 'Addis Ababa', country: 'Ethiopia', timezone: 'Africa/Addis_Ababa', region: 'Africa' },
    { city: 'Asmara', country: 'Eritrea', timezone: 'Africa/Asmara', region: 'Africa' },
    { city: 'Djibouti', country: 'Djibouti', timezone: 'Africa/Djibouti', region: 'Africa' },
    { city: 'Mogadishu', country: 'Somalia', timezone: 'Africa/Mogadishu', region: 'Africa' },
    { city: 'Dar es Salaam', country: 'Tanzania', timezone: 'Africa/Dar_es_Salaam', region: 'Africa' },
    { city: 'Kampala', country: 'Uganda', timezone: 'Africa/Kampala', region: 'Africa' },
    { city: 'Kigali', country: 'Rwanda', timezone: 'Africa/Kigali', region: 'Africa' },
    { city: 'Bujumbura', country: 'Burundi', timezone: 'Africa/Bujumbura', region: 'Africa' },
    { city: 'Kinshasa', country: 'DR Congo', timezone: 'Africa/Kinshasa', region: 'Africa' },
    { city: 'Lubumbashi', country: 'DR Congo', timezone: 'Africa/Lubumbashi', region: 'Africa' },
    { city: 'Brazzaville', country: 'Republic of Congo', timezone: 'Africa/Brazzaville', region: 'Africa' },
    { city: 'Yaounde', country: 'Cameroon', timezone: 'Africa/Douala', region: 'Africa' },
    { city: 'Accra', country: 'Ghana', timezone: 'Africa/Accra', region: 'Africa' },
    { city: 'Abidjan', country: 'Ivory Coast', timezone: 'Africa/Abidjan', region: 'Africa' },
    { city: 'Dakar', country: 'Senegal', timezone: 'Africa/Dakar', region: 'Africa' },
    { city: 'Bamako', country: 'Mali', timezone: 'Africa/Bamako', region: 'Africa' },
    { city: 'Ouagadougou', country: 'Burkina Faso', timezone: 'Africa/Ouagadougou', region: 'Africa' },
    { city: 'Niamey', country: 'Niger', timezone: 'Africa/Niamey', region: 'Africa' },
    { city: 'NDjamena', country: 'Chad', timezone: 'Africa/NDjamena', region: 'Africa' },
    { city: 'Bangui', country: 'Central African Republic', timezone: 'Africa/Bangui', region: 'Africa' },
    { city: 'Libreville', country: 'Gabon', timezone: 'Africa/Libreville', region: 'Africa' },
    { city: 'Malabo', country: 'Equatorial Guinea', timezone: 'Africa/Malabo', region: 'Africa' },
    { city: 'Sao Tome', country: 'São Tomé & Príncipe', timezone: 'Africa/Sao_Tome', region: 'Africa' },
    { city: 'Luanda', country: 'Angola', timezone: 'Africa/Luanda', region: 'Africa' },
    { city: 'Windhoek', country: 'Namibia', timezone: 'Africa/Windhoek', region: 'Africa' },
    { city: 'Gaborone', country: 'Botswana', timezone: 'Africa/Gaborone', region: 'Africa' },
    { city: 'Harare', country: 'Zimbabwe', timezone: 'Africa/Harare', region: 'Africa' },
    { city: 'Lusaka', country: 'Zambia', timezone: 'Africa/Lusaka', region: 'Africa' },
    { city: 'Lilongwe', country: 'Malawi', timezone: 'Africa/Blantyre', region: 'Africa' },
    { city: 'Maputo', country: 'Mozambique', timezone: 'Africa/Maputo', region: 'Africa' },
    { city: 'Antananarivo', country: 'Madagascar', timezone: 'Indian/Antananarivo', region: 'Africa' },
    { city: 'Moroni', country: 'Comoros', timezone: 'Indian/Comoro', region: 'Africa' },
    { city: 'Victoria', country: 'Seychelles', timezone: 'Indian/Mahe', region: 'Africa' },
    { city: 'Port Louis', country: 'Mauritius', timezone: 'Indian/Mauritius', region: 'Africa' },
    { city: 'Saint-Denis', country: 'Réunion', timezone: 'Indian/Reunion', region: 'Africa' },
    { city: 'Conakry', country: 'Guinea', timezone: 'Africa/Conakry', region: 'Africa' },
    { city: 'Bissau', country: 'Guinea-Bissau', timezone: 'Africa/Bissau', region: 'Africa' },
    { city: 'Freetown', country: 'Sierra Leone', timezone: 'Africa/Freetown', region: 'Africa' },
    { city: 'Monrovia', country: 'Liberia', timezone: 'Africa/Monrovia', region: 'Africa' },
    { city: 'Lome', country: 'Togo', timezone: 'Africa/Lome', region: 'Africa' },
    { city: 'Cotonou', country: 'Benin', timezone: 'Africa/Porto-Novo', region: 'Africa' },
    { city: 'Banjul', country: 'Gambia', timezone: 'Africa/Banjul', region: 'Africa' },
    { city: 'Praia', country: 'Cape Verde', timezone: 'Atlantic/Cape_Verde', region: 'Africa' },
    { city: 'Nouakchott', country: 'Mauritania', timezone: 'Africa/Nouakchott', region: 'Africa' },
    { city: 'El Aaiun', country: 'Western Sahara', timezone: 'Africa/El_Aaiun', region: 'Africa' },
    { city: 'Mbabane', country: 'Eswatini', timezone: 'Africa/Mbabane', region: 'Africa' },
    { city: 'Maseru', country: 'Lesotho', timezone: 'Africa/Maseru', region: 'Africa' },
];

const page = usePage();
const isLoaded = ref(false);

// Initialize timezones from server props immediately
const savedTimezones = (page.props.timezonePreferences as any[] || [])
    .map((tz: any) => AVAILABLE_TIMEZONES.find(t => t.timezone === tz.timezone))
    .filter((tz): tz is TimezoneData => tz !== undefined)
    .slice(0, 5); // Max 5 additional timezones

// Timezone management state
const selectedTimezones = ref<TimezoneData[]>(savedTimezones);
const showTimezonePicker = ref(false);
const timezoneSearchQuery = ref('');

// Save timezones to database
const saveTimezones = async () => {
    try {
        await axios.post('/api/user/timezone-preferences', {
            timezones: selectedTimezones.value
        });
    } catch (e) {
        console.error('Error saving timezones:', e);
    }
};

// Add timezone
const addTimezone = async (timezone: TimezoneData) => {
    if (selectedTimezones.value.length < 5 && !selectedTimezones.value.find(t => t.timezone === timezone.timezone)) {
        selectedTimezones.value.push(timezone);
        await saveTimezones();
        // Keep modal open to allow adding multiple timezones
        // User can click "Done" button when finished
    }
};

// Remove timezone
const removeTimezone = async (index: number) => {
    selectedTimezones.value.splice(index, 1);
    await saveTimezones();
};

// Close timezone picker modal
const closeTimezonePicker = () => {
    showTimezonePicker.value = false;
    timezoneSearchQuery.value = '';
};

// Filtered timezones for search
const filteredTimezones = computed(() => {
    const query = timezoneSearchQuery.value.toLowerCase().trim();
    if (!query) return AVAILABLE_TIMEZONES;

    return AVAILABLE_TIMEZONES.filter(tz =>
        tz.city.toLowerCase().includes(query) ||
        tz.country.toLowerCase().includes(query) ||
        tz.region.toLowerCase().includes(query)
    );
});

// Group filtered timezones by region
const groupedTimezones = computed(() => {
    const grouped: Record<string, TimezoneData[]> = {};
    filteredTimezones.value.forEach(tz => {
        if (!grouped[tz.region]) grouped[tz.region] = [];
        grouped[tz.region].push(tz);
    });
    return grouped;
});

// Check if timezone is already selected
const isTimezoneSelected = (timezone: TimezoneData) => {
    return selectedTimezones.value.some(t => t.timezone === timezone.timezone);
};

const currentTime = ref(new Date());
let timeInterval: NodeJS.Timeout;
const isDarkMode = ref(false);
let themeObserver: MutationObserver | null = null;

onMounted(() => {
    isDarkMode.value = document.documentElement.classList.contains('dark');
    themeObserver = new MutationObserver(() => {
        isDarkMode.value = document.documentElement.classList.contains('dark');
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    timeInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);

    setTimeout(() => {
        isLoaded.value = true;
    }, 100);
});

onUnmounted(() => {
    if (timeInterval) {
        clearInterval(timeInterval);
    }

    if (themeObserver) {
        themeObserver.disconnect();
        themeObserver = null;
    }
});

// Helper function to determine time of day styling
const getTimeOfDayStyle = (dateTime: Date) => {
    const hour = dateTime.getHours();

    if (hour >= 5 && hour < 7) {
        // Early Morning (Dawn) - soft pinks and light blues
        return {
            gradient: 'from-pink-200 via-orange-100 to-blue-200',
            darkGradient: 'dark:from-pink-900/40 dark:via-orange-900/40 dark:to-blue-900/40',
            period: '🌅 Dawn',
            textColor: 'text-orange-900 dark:text-orange-200'
        };
    } else if (hour >= 7 && hour < 12) {
        // Morning - warm yellows and light
        return {
            gradient: 'from-yellow-200 via-amber-100 to-orange-100',
            darkGradient: 'dark:from-yellow-900/40 dark:via-amber-900/40 dark:to-orange-900/40',
            period: '☀️ Morning',
            textColor: 'text-amber-900 dark:text-amber-200'
        };
    } else if (hour >= 12 && hour < 17) {
        // Afternoon - bright and vibrant
        return {
            gradient: 'from-sky-200 via-cyan-100 to-blue-200',
            darkGradient: 'dark:from-sky-900/40 dark:via-cyan-900/40 dark:to-blue-900/40',
            period: '🌞 Afternoon',
            textColor: 'text-sky-900 dark:text-sky-200'
        };
    } else if (hour >= 17 && hour < 19) {
        // Evening (Dusk) - oranges and purples
        return {
            gradient: 'from-orange-200 via-pink-200 to-purple-200',
            darkGradient: 'dark:from-orange-900/40 dark:via-pink-900/40 dark:to-purple-900/40',
            period: '🌆 Dusk',
            textColor: 'text-purple-900 dark:text-purple-200'
        };
    } else if (hour >= 19 && hour < 22) {
        // Evening - deep oranges to blues
        return {
            gradient: 'from-indigo-200 via-purple-200 to-blue-300',
            darkGradient: 'dark:from-indigo-900/40 dark:via-purple-900/40 dark:to-blue-900/40',
            period: '🌃 Evening',
            textColor: 'text-indigo-900 dark:text-indigo-200'
        };
    } else {
        // Night - deep blues and purples
        return {
            gradient: 'from-indigo-300 via-blue-400 to-slate-400',
            darkGradient: 'dark:from-indigo-950/60 dark:via-blue-950/60 dark:to-slate-950/60',
            period: '🌙 Night',
            textColor: 'text-slate-100 dark:text-slate-300'
        };
    }
};

// Generic function to compute timezone data
const getTimezoneData = (timezone: string) => {
    const tzTime = new Date(currentTime.value.toLocaleString("en-US", { timeZone: timezone }));
    const style = getTimeOfDayStyle(tzTime);
    const formattedTime = tzTime.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    }).replace(/:/g, '·');
    return {
        time: formattedTime,
        date: tzTime.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric'
        }),
        ...style
    };
};

// Static timezones (always shown)
// Netherlands Time (Europe/Amsterdam)
const netherlandsTime = computed(() => getTimezoneData("Europe/Amsterdam"));

// Dynamic timezones (user customizable)
const dynamicTimezones = computed(() => {
    return selectedTimezones.value.map(tz => ({
        ...tz,
        ...getTimezoneData(tz.timezone)
    }));
});

// Compute grid layout based on total timezone count
const timezoneGridClass = computed(() => {
    const totalCount = 1 + selectedTimezones.value.length; // 1 static + dynamic

    if (totalCount === 1) {
        // 1 timezone: single column
        return 'grid-cols-1';
    } else if (totalCount === 2) {
        // 2 timezones: 1 column mobile, 2 columns tablet+
        return 'grid-cols-1 sm:grid-cols-2';
    } else if (totalCount === 3) {
        // 3 timezones: 1 column mobile, 2 columns tablet, 3 columns desktop
        return 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3';
    } else if (totalCount === 4) {
        // 4 timezones: 1 column mobile, 2x2 grid on larger screens
        return 'grid-cols-1 sm:grid-cols-2';
    } else {
        // 5-6 timezones: 1 column mobile, 2 columns tablet, 3 columns desktop
        return 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3';
    }
});

const stats = computed(() => page.props.monthlyStats ?? {});
const year = new Date().getFullYear();
const currentMonth = new Date().getMonth();

const labels = Array.from({ length: 12 }, (_, i) =>
    new Date(0, i).toLocaleString('default', { month: 'short' })
);

const datasets = computed(() => [
    {
        label: 'Banners',
        data: labels.map((_, i) => stats.value.banners?.[i + 1] || 0),
        borderColor: '#000000',
        backgroundColor: 'rgba(0, 0, 0, 0.05)',
        borderWidth: 2,
        tension: 0.4,
        fill: true,
    },
    {
        label: 'Videos',
        data: labels.map((_, i) => stats.value.videos?.[i + 1] || 0),
        borderColor: '#666666',
        backgroundColor: 'rgba(102, 102, 102, 0.05)',
        borderWidth: 2,
        tension: 0.4,
        fill: true,
    },
    {
        label: 'GIFs',
        data: labels.map((_, i) => stats.value.gifs?.[i + 1] || 0),
        borderColor: '#999999',
        backgroundColor: 'rgba(153, 153, 153, 0.05)',
        borderWidth: 2,
        tension: 0.4,
        fill: true,
    },
    {
        label: 'Socials',
        data: labels.map((_, i) => stats.value.socials?.[i + 1] || 0),
        borderColor: '#D71921',
        backgroundColor: 'rgba(215, 25, 33, 0.05)',
        borderWidth: 2,
        tension: 0.4,
        fill: true,
    },
]);

const chartData = computed(() => ({
    labels,
    datasets: datasets.value,
}));

const chartTheme = computed(() => ({
    axisText: isDarkMode.value ? '#999999' : '#666666',
    titleText: isDarkMode.value ? '#E8E8E8' : '#1A1A1A',
    gridLine: isDarkMode.value ? '#222222' : '#E8E8E8',
    tooltipBg: isDarkMode.value ? 'rgba(255, 255, 255, 0.92)' : 'rgba(0, 0, 0, 0.9)',
    tooltipText: isDarkMode.value ? '#000000' : '#FFFFFF',
    tooltipBorder: isDarkMode.value ? '#CCCCCC' : '#222222',
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index' as const,
        intersect: false,
    },
    animation: {
        duration: 300,
        easing: 'easeOutCubic' as const,
    },
    plugins: {
        legend: {
            position: 'bottom' as const,
            labels: {
                padding: 16,
                usePointStyle: true,
                pointStyle: 'circle' as const,
                font: {
                    size: 11,
                    family: 'monospace',
                },
                color: chartTheme.value.axisText,
            }
        },
        title: {
            display: true,
            text: `YEARLY CONTENT STATISTICS (${year})`,
            font: {
                size: 12,
                weight: '400',
                family: 'monospace',
            },
            padding: 20,
            color: chartTheme.value.titleText,
        },
        tooltip: {
            backgroundColor: chartTheme.value.tooltipBg,
            titleColor: chartTheme.value.tooltipText,
            bodyColor: chartTheme.value.tooltipText,
            borderColor: chartTheme.value.tooltipBorder,
            borderWidth: 1,
            padding: 12,
            titleFont: {
                family: 'monospace',
                size: 11,
            },
            bodyFont: {
                family: 'monospace',
                size: 11,
            },
        }
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: {
                font: {
                    weight: '400',
                    family: 'monospace',
                    size: 10,
                },
                color: chartTheme.value.axisText
            }
        },
        y: {
            beginAtZero: true,
            grid: {
                color: chartTheme.value.gridLine,
                lineWidth: 1,
            },
            ticks: {
                font: {
                    family: 'monospace',
                    size: 10,
                },
                color: chartTheme.value.axisText
            }
        }
    }
}));

// Enhanced count-up animation
const animateCount = (target: number, duration: number = 2000) => {
    const count = ref(0);

    watchEffect((onCleanup) => {
        if (!isLoaded.value) return;

        let current = 0;
        const increment = target / (duration / 16);

        const animate = () => {
            current += increment;
            if (current >= target) {
                count.value = target;
            } else {
                count.value = Math.floor(current);
                requestAnimationFrame(animate);
            }
        };

        const timeout = setTimeout(animate, Math.random() * 500);
        onCleanup(() => clearTimeout(timeout));
    });

    return count;
};

const animatedCounts = {
    userCount: animateCount(page.props.userCount, 2000),
    previewCount: animateCount(page.props.previewCount, 1000),
    bannerCount: animateCount(page.props.bannerCount, 1000),
    videoCount: animateCount(page.props.videoCount, 1000),
    gifCount: animateCount(page.props.gifCount, 1000),
    socialCount: animateCount(page.props.socialCount, 2600),
    fileTransferCount: animateCount(page.props.fileTransferCount, 2000),
    totalBill: animateCount(page.props.totalBill, 2800),
};

const monthlyBillTotals = computed(() => page.props.monthlyBillTotals ?? {});
const monthlyPreviewStats = computed(() => page.props.monthlyPreviewStats ?? {});

// Doughnut chart for content distribution
const contentDistributionData = computed(() => ({
    labels: ['Banners', 'Videos', 'GIFs', 'Socials'],
    datasets: [{
        data: [
            page.props.bannerCount,
            page.props.videoCount,
            page.props.gifCount,
            page.props.socialCount
        ],
        backgroundColor: [
            '#000000',
            '#666666',
            '#999999',
            '#D71921'
        ],
        borderWidth: 0,
        hoverOffset: 4,
    }]
}));

const doughnutOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 300,
        easing: 'easeOutCubic' as const,
    },
    plugins: {
        legend: {
            position: 'bottom' as const,
            labels: {
                padding: 12,
                usePointStyle: true,
                font: {
                    size: 11,
                    family: 'monospace',
                },
                color: chartTheme.value.axisText,
            }
        },
        title: {
            display: true,
            text: 'CONTENT DISTRIBUTION',
            font: {
                size: 12,
                weight: '400',
                family: 'monospace',
            },
            padding: 20,
            color: chartTheme.value.titleText,
        }
    },
    cutout: '60%',
}));

const billChartData = computed(() => ({
    labels,
    datasets: [
        {
            label: 'Monthly Bills (BDT)',
            data: labels.map((_, i) => monthlyBillTotals.value[i + 1] || 0),
            backgroundColor: '#000000',
            borderColor: '#000000',
            borderWidth: 0,
            borderRadius: 4,
            borderSkipped: false,
        },
    ],
}));

const billChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 300,
        easing: 'easeOutCubic' as const,
    },
    plugins: {
        legend: {
            display: false
        },
        title: {
            display: true,
            text: `MONTHLY BILLS OVERVIEW (${year})`,
            font: {
                size: 12,
                weight: '400',
                family: 'monospace',
            },
            padding: 20,
            color: chartTheme.value.titleText,
        },
        tooltip: {
            backgroundColor: chartTheme.value.tooltipBg,
            titleColor: chartTheme.value.tooltipText,
            bodyColor: chartTheme.value.tooltipText,
            titleFont: {
                family: 'monospace',
                size: 11,
            },
            bodyFont: {
                family: 'monospace',
                size: 11,
            },
        }
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: {
                font: {
                    weight: '400',
                    family: 'monospace',
                    size: 10,
                },
                color: chartTheme.value.axisText,
            }
        },
        y: {
            beginAtZero: true,
            grid: {
                color: chartTheme.value.gridLine,
                lineWidth: 1,
            },
            ticks: {
                font: {
                    family: 'monospace',
                    size: 10,
                },
                color: chartTheme.value.axisText,
                callback: (value) => `৳${value}`
            }
        }
    }
}));

const previewChartData = computed(() => ({
    labels,
    datasets: [
        {
            label: 'Monthly Previews',
            data: labels.map((_, i) => monthlyPreviewStats.value[i + 1] || 0),
            backgroundColor: '#666666',
            borderColor: '#666666',
            borderWidth: 0,
            borderRadius: 4,
            borderSkipped: false,
            hoverBackgroundColor: '#000000',
            hoverBorderColor: '#000000',
            hoverBorderWidth: 0,
        },
    ],
}));

const previewChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 300,
        easing: 'easeOutCubic' as const,
    },
    plugins: {
        legend: {
            display: false
        },
        title: {
            display: true,
            text: `MONTHLY PREVIEWS STATISTICS (${year})`,
            font: {
                size: 12,
                weight: '400',
                family: 'monospace',
            },
            padding: 20,
            color: chartTheme.value.titleText,
        },
        tooltip: {
            backgroundColor: chartTheme.value.tooltipBg,
            titleColor: chartTheme.value.tooltipText,
            bodyColor: chartTheme.value.tooltipText,
            borderColor: chartTheme.value.tooltipBorder,
            borderWidth: 1,
            padding: 12,
            titleFont: {
                family: 'monospace',
                size: 11,
            },
            bodyFont: {
                family: 'monospace',
                size: 11,
            },
            callbacks: {
                title: (context) => `${context[0].label} ${year}`,
                label: (context) => `Previews: ${context.parsed.y}`,
            }
        }
    },
    scales: {
        x: {
            grid: {
                display: false
            },
            ticks: {
                font: {
                    weight: '400',
                    family: 'monospace',
                    size: 10,
                },
                color: chartTheme.value.axisText
            }
        },
        y: {
            beginAtZero: true,
            grid: {
                color: chartTheme.value.gridLine,
                lineWidth: 1
            },
            ticks: {
                font: {
                    family: 'monospace',
                    size: 10,
                },
                color: chartTheme.value.axisText,
                callback: (value) => `${value} previews`
            }
        }
    }
}));

// Calculate growth percentages
const currentMonthData = computed(() => {
    const current = currentMonth + 1;
    const previous = current === 1 ? 12 : current - 1;

    return {
        previews: {
            current: monthlyPreviewStats.value[current] || 0,
            previous: monthlyPreviewStats.value[previous] || 0,
        },
        bills: {
            current: monthlyBillTotals.value[current] || 0,
            previous: monthlyBillTotals.value[previous] || 0,
        }
    };
});

const calculateGrowth = (current: number, previous: number) => {
    if (previous === 0) return current > 0 ? 100 : 0;
    return Math.round(((current - previous) / previous) * 100);
};

const formatNumber = (num: number) => {
    return new Intl.NumberFormat().format(num);
};
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-[#FFFFFF] dark:bg-black font-mono">
            <div class="p-6 space-y-12">
                <!-- Header -->
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-8 lg:gap-0">
                    <div class="flex-1">
                        <h1 class="text-4xl md:text-5xl font-light tracking-tight text-black dark:text-white uppercase">
                            DASHBOARD
                        </h1>
                        <p class="text-[#666666] dark:text-[#999999] mt-2 text-lg tracking-widest uppercase font-mono">
                            ANALYTICS — {{ year }}
                        </p>
                    </div>

                    <!-- World Clocks - Nothing Design -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xs tracking-widest uppercase font-mono text-[#666666] dark:text-[#999999]">
                                WORLD TIME</h2>
                            <button v-if="selectedTimezones.length < 5" @click="showTimezonePicker = true"
                                class="px-3 py-1.5 rounded-full transition-all duration-200 text-xs tracking-wider uppercase border-2 bg-black text-white border-white hover:bg-white hover:text-black hover:border-black dark:bg-white dark:text-black dark:hover:bg-black dark:hover:text-white dark:hover:border-white"
                                title="Add timezone">
                                <Plus :size="14" class="inline -mt-0.5" /> ADD
                            </button>
                        </div>

                        <div
                            class="bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded-lg p-2.5 md:p-3">
                            <!-- Mobile: Single Column | Tablet: 2 Columns | Desktop: 3x2 Grid -->
                            <div :class="['grid gap-2.5', timezoneGridClass]">
                                <!-- Netherlands (Static) -->
                                <DotMatrixClock :time="netherlandsTime.time" :date="netherlandsTime.date"
                                    city="Amsterdam" country="Netherlands" size="sm" />

                                <!-- Dynamic Timezones (User Customizable) -->
                                <div v-for="(tz, index) in dynamicTimezones" :key="tz.timezone" class="relative group">

                                    <!-- Remove Button -->
                                    <button @click="removeTimezone(index)"
                                        class="absolute -top-2 -right-2 p-1.5 bg-[#D71921] text-white rounded-full opacity-0 group-hover:opacity-100 transition-all duration-200 z-20 shadow-lg hover:bg-[#B01519]"
                                        title="Remove timezone">
                                        <X :size="14" />
                                    </button>

                                    <DotMatrixClock :time="tz.time" :date="tz.date" :city="tz.city"
                                        :country="tz.country" size="sm" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid - Nothing Design -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Users Card -->
                    <div
                        class="bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded-lg p-6 transition-all duration-200 hover:border-[#CCCCCC] dark:hover:border-[#333333]">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p
                                    class="text-xs font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                    TOTAL USERS</p>
                                <p class="text-4xl font-light text-black dark:text-white mt-3 font-mono tabular-nums">
                                    {{ formatNumber(animatedCounts.userCount.value) }}
                                </p>
                            </div>
                            <div class="p-2">
                                <UsersRound class="w-5 h-5 text-[#666666] dark:text-[#999999]" :stroke-width="1.5" />
                            </div>
                        </div>
                    </div>

                    <!-- Previews Card -->
                    <div
                        class="bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded-lg p-6 transition-all duration-200 hover:border-[#CCCCCC] dark:hover:border-[#333333]">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p
                                    class="text-xs font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                    TOTAL PREVIEWS</p>
                                <p class="text-4xl font-light text-black dark:text-white mt-3 font-mono tabular-nums">
                                    {{ formatNumber(animatedCounts.previewCount.value) }}
                                </p>
                            </div>
                            <div class="p-2">
                                <MonitorStop class="w-5 h-5 text-[#666666] dark:text-[#999999]" :stroke-width="1.5" />
                            </div>
                        </div>
                    </div>

                    <!-- Content Cards Row -->
                    <div class="col-span-1 sm:col-span-2 lg:col-span-2 grid grid-cols-2 gap-4">
                        <div
                            class="bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded-lg p-4 transition-all duration-200 hover:border-[#CCCCCC] dark:hover:border-[#333333]">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p
                                        class="text-[10px] font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                        BANNERS</p>
                                    <p
                                        class="text-2xl font-light text-black dark:text-white mt-2 font-mono tabular-nums">
                                        {{ formatNumber(animatedCounts.bannerCount.value) }}
                                    </p>
                                </div>
                                <div class="p-1">
                                    <MonitorCog class="w-4 h-4 text-[#666666] dark:text-[#999999]"
                                        :stroke-width="1.5" />
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded-lg p-4 transition-all duration-200 hover:border-[#CCCCCC] dark:hover:border-[#333333]">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p
                                        class="text-[10px] font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                        VIDEOS</p>
                                    <p
                                        class="text-2xl font-light text-black dark:text-white mt-2 font-mono tabular-nums">
                                        {{ formatNumber(animatedCounts.videoCount.value) }}
                                    </p>
                                </div>
                                <div class="p-1">
                                    <Video class="w-4 h-4 text-[#666666] dark:text-[#999999]" :stroke-width="1.5" />
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded-lg p-4 transition-all duration-200 hover:border-[#CCCCCC] dark:hover:border-[#333333]">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p
                                        class="text-[10px] font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                        GIFS</p>
                                    <p
                                        class="text-2xl font-light text-black dark:text-white mt-2 font-mono tabular-nums">
                                        {{ formatNumber(animatedCounts.gifCount.value) }}
                                    </p>
                                </div>
                                <div class="p-1">
                                    <ImagePlay class="w-4 h-4 text-[#666666] dark:text-[#999999]" :stroke-width="1.5" />
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded-lg p-4 transition-all duration-200 hover:border-[#CCCCCC] dark:hover:border-[#333333]">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p
                                        class="text-[10px] font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                        SOCIALS</p>
                                    <p
                                        class="text-2xl font-light text-black dark:text-white mt-2 font-mono tabular-nums">
                                        {{ formatNumber(animatedCounts.socialCount.value) }}
                                    </p>
                                </div>
                                <div class="p-1">
                                    <Wallpaper class="w-4 h-4 text-[#666666] dark:text-[#999999]" :stroke-width="1.5" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Stats Row - Nothing Design -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div
                        class="bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded-lg p-6 transition-all duration-200 hover:border-[#CCCCCC] dark:hover:border-[#333333]">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p
                                    class="text-xs font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                    FILE TRANSFERS</p>
                                <p class="text-4xl font-light text-black dark:text-white mt-3 font-mono tabular-nums">
                                    {{ formatNumber(animatedCounts.fileTransferCount.value) }}
                                </p>
                            </div>
                            <div class="p-2">
                                <Paperclip class="w-5 h-5 text-[#666666] dark:text-[#999999]" :stroke-width="1.5" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-[#111111] border-2 border-black dark:border-white rounded-lg p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p
                                    class="text-xs font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                    TOTAL BILLS</p>
                                <p class="text-4xl font-light text-black dark:text-white mt-3 font-mono tabular-nums">
                                    ৳{{ formatNumber(animatedCounts.totalBill.value) }}
                                </p>
                                <div
                                    class="flex items-center mt-3 border-t border-[#E8E8E8] dark:border-[#222222] pt-3">
                                    <span class="text-xs font-mono text-[#1A1A1A] dark:text-[#E8E8E8]">
                                        {{ calculateGrowth(currentMonthData.bills.current,
                                            currentMonthData.bills.previous) }}%
                                    </span>
                                    <span
                                        class="text-xs text-[#666666] dark:text-[#999999] ml-2 uppercase tracking-wider">VS
                                        LAST MONTH</span>
                                </div>
                            </div>
                            <div class="p-2">
                                <PiggyBank class="w-5 h-5 text-black dark:text-white" :stroke-width="1.5" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section - Nothing Design -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 ">
                    <!-- Content Trends Chart -->
                    <div
                        class="lg:col-span-2 bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded-lg p-6">
                        <div style="height: 400px;">
                            <Line v-if="chartData && chartData.datasets" :data="chartData" :options="chartOptions" />
                            <div v-else
                                class="flex items-center justify-center h-full text-[#666666] dark:text-[#999999] font-mono text-xs uppercase tracking-wider">
                                [LOADING CHART...]
                            </div>
                        </div>
                    </div>

                    <!-- Content Distribution Pie Chart -->
                    <div
                        class="bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded-lg p-6">
                        <div style="height: 400px;">
                            <Doughnut :data="contentDistributionData" :options="doughnutOptions" />
                        </div>
                    </div>
                </div>

                <!-- Bottom Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Preview Statistics -->
                    <div
                        class="bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded-lg p-6">
                        <div style="height: 350px;">
                            <Bar :data="previewChartData" :options="previewChartOptions" />
                        </div>
                    </div>

                    <!-- Bills Overview -->
                    <div
                        class="bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded-lg p-6">
                        <div style="height: 350px;">
                            <Bar :data="billChartData" :options="billChartOptions" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timezone Picker Modal - Nothing Design -->
        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showTimezonePicker" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80"
                @click.self="closeTimezonePicker">

                <div
                    class="bg-white dark:bg-[#111111] rounded-lg max-w-2xl w-full max-h-[80vh] overflow-hidden border-2 border-black dark:border-white">

                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-4 border-b border-[#E8E8E8] dark:border-[#222222]">
                        <h3 class="text-sm font-mono uppercase tracking-widest text-black dark:text-white">ADD TIMEZONES
                        </h3>
                        <button @click="closeTimezonePicker"
                            class="p-1.5 border border-[#E8E8E8] dark:border-[#222222] rounded-full transition-colors hover:border-black dark:hover:border-white">
                            <X :size="16" class="text-[#666666] dark:text-[#999999]" />
                        </button>
                    </div>

                    <!-- Search Bar -->
                    <div class="p-4 border-b border-[#E8E8E8] dark:border-[#222222]">
                        <div class="relative">
                            <Search :size="16"
                                class="absolute left-3 top-1/2 -translate-y-1/2 text-[#666666] dark:text-[#999999]" />
                            <input v-model="timezoneSearchQuery" type="text"
                                placeholder="SEARCH CITY, COUNTRY, OR REGION..."
                                class="w-full pl-10 pr-4 py-2 border border-[#CCCCCC] dark:border-[#333333] rounded bg-[#F5F5F5] dark:bg-black text-[#1A1A1A] dark:text-[#E8E8E8] placeholder-[#999999] dark:placeholder-[#666666] focus:outline-none focus:border-black dark:focus:border-white font-mono text-xs uppercase tracking-wider"
                                autofocus>
                        </div>
                    </div>

                    <!-- Timezone List -->
                    <div class="overflow-y-auto max-h-96 p-4">
                        <div v-if="filteredTimezones.length === 0"
                            class="text-center py-8 text-[#666666] dark:text-[#999999] font-mono text-xs uppercase tracking-wider">
                            [NO TIMEZONES FOUND]
                        </div>

                        <div v-else class="space-y-4">
                            <div v-for="(timezones, region) in groupedTimezones" :key="region">
                                <h4
                                    class="text-xs font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999] mb-2 px-2">
                                    {{ region }}
                                </h4>
                                <div class="space-y-1">
                                    <button v-for="tz in timezones" :key="tz.timezone" @click="addTimezone(tz)"
                                        :disabled="isTimezoneSelected(tz)"
                                        class="w-full flex items-center justify-between p-3 border border-transparent rounded transition-all duration-200 hover:border-[#CCCCCC] dark:hover:border-[#333333] disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:border-transparent">
                                        <div class="flex flex-col items-start">
                                            <span
                                                class="font-medium text-[#1A1A1A] dark:text-[#E8E8E8] text-sm uppercase tracking-wide">{{
                                                    tz.city }}</span>
                                            <span
                                                class="text-xs text-[#666666] dark:text-[#999999] uppercase font-mono tracking-wider">{{
                                                    tz.country
                                                }}</span>
                                        </div>
                                        <span v-if="isTimezoneSelected(tz)"
                                            class="text-xs px-2 py-1 border border-black dark:border-white text-black dark:text-white rounded-full uppercase font-mono tracking-wider">
                                            ADDED
                                        </span>
                                        <Plus v-else :size="16" class="text-[#666666] dark:text-[#999999]" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 border-t border-[#E8E8E8] dark:border-[#222222] bg-[#F5F5F5] dark:bg-black">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-[#666666] dark:text-[#999999] uppercase font-mono tracking-wider">
                                {{ selectedTimezones.length }} / 5 TIMEZONES
                            </span>
                            <button @click="closeTimezonePicker"
                                class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-full transition-colors hover:bg-[#1A1A1A] dark:hover:bg-[#E8E8E8] uppercase font-mono text-xs tracking-wider">
                                DONE
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>

<style scoped>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.group:hover .absolute {
    animation: pulse 2s infinite;
}

@keyframes pulse {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: .5;
    }
}
</style>