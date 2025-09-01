@extends('layouts.app')
@section('title', 'Home')

@section('content')
<section class="w-full mx-auto max-w-[1470px]">
    @if(isset($data['rewardDate']))
    <div>
        <div class="flex items-center justify-between gap-2 mt-5 mb-2 border border-[#bd97ff] p-2 sm:pl-4 rounded-md">
            <h2 class="text-sm md:text-xl font-semibold leading-none">Reward Bonus (Star {{$data['user']['rank_id'] + 1}})</h2>
            @if(count($data['my_packages'])>0)
            <div id="timer" class="flex justify-center text-sm sm:text-base md:text-2xl font-semibold gap-1 sm:gap-2">
                <div class="text-center">
                    <div id="days" class="text-[#fad85d]">--</div>
                    <div class="text-xs sm:text-sm text-gray-400">Days</div>
                </div>
                <span>:</span>
                <div class="text-center">
                    <div id="hours" class="text-[#fad85d]">--</div>
                    <div class="text-xs sm:text-sm text-gray-400">Hours</div>
                </div>
                <span>:</span>
                <div class="text-center">
                    <div id="minutes" class="text-[#fad85d]">--</div>
                    <div class="text-xs sm:text-sm text-gray-400">Minutes</div>
                </div>
                <span>:</span>
                <div class="text-center">
                    <div id="seconds" class="text-[#fad85d]">--</div>
                    <div class="text-xs sm:text-sm text-gray-400">Seconds</div>
                </div>
            </div>
            @endif
        </div>

        <script>
            const countdownDate = new Date("{{ date('c', strtotime($data['rewardDate'])) }}").getTime();
            const daysEl = document.getElementById("days");
            const hoursEl = document.getElementById("hours");
            const minutesEl = document.getElementById("minutes");
            const secondsEl = document.getElementById("seconds");

            function updateCountdown() {
                const now = new Date().getTime();
                const distance = countdownDate - now;

                if (distance <= 0) {
                    daysEl.innerText = hoursEl.innerText = minutesEl.innerText = secondsEl.innerText = '00';
                    clearInterval(interval);
                    return;
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                daysEl.innerText = String(days).padStart(2, '0');
                hoursEl.innerText = String(hours).padStart(2, '0');
                minutesEl.innerText = String(minutes).padStart(2, '0');
                secondsEl.innerText = String(seconds).padStart(2, '0');
            }

            const interval = setInterval(updateCountdown, 1000);
            updateCountdown();
        </script>
    </div>
    @endif
    <div class="grid grid-cols-1 gap-5 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 md:gap-5 overflow-hidden mt-5">
            <x-stat-box
                title="Market Value"
                value="${{ number_format(210000000 * $data['rtxPrice']) }}"
                bgColor="#0BF4C8"
                borderColor="#0BF4C8"
                imageSrc="{{ asset('assets/images/icons/marketvalue.webp') }}"
                altText="Market Value" />

            <x-stat-box
                title="Total Supply"
                value="{{ number_format(210000000) }}"
                bgColor="#FAD85D"
                borderColor="#FAD85D"
                imageSrc="{{ asset('assets/images/icons/totalsupply.webp') }}"
                altText="Total Supply" />

            <x-stat-box
                title="RTX Price"
                value="${{ number_format($data['rtxPrice'], 2) }}"
                bgColor="#F2A0FF"
                borderColor="#F2A0FF"
                imageSrc="{{ asset('assets/images/icons/rtxprice.webp') }}"
                altText="RTX Price" />

            <x-stat-box
                title="Liquidity Pool"
                value="${{ number_format($data['treasuryBalance'] * $data['rtxPrice'], 2) }}"
                bgColor="#FF6B6B"
                borderColor="#FF6B6B"
                imageSrc="{{ asset('assets/images/icons/treasury-wallet.webp') }}"
                altText="Liquidity Pool" />
        </div>
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <div class="grid grid-cols-1 xl:col-span-2 gap-5">
                <x-star-levels :rank="$data['user']['rank_id']" />
            </div>
            <div class="grid grid-cols-1 col-span-1 gap-5">
                <x-referral-link-card
                    package="{{count($data['my_packages'])}}"
                    walletAddress="{{$data['user']['wallet_address']}}"
                    referrer="{{$data['sponser']['wallet_address']}}"
                    affiliateData="{referrer: {{$data['sponser']['wallet_address']}}}"
                    link="https://{{ request()->getHost() }}/connect-wallet?sponser_code={{ Session::get('wallet_address')}}" />

                <x-download-pdf
                    file="orbitx-the-defi-revolution-begins.pdf?v={{time()}}"
                    title="PPT Download"
                    subtitle="Download Orbitx Presentation"
                    logo="assets/images/logo.webp"
                    bgImage="assets/images/wavebgbox.svg" />
            </div>
        </div>
        <x-level-grid :currentLevel="$data['user']['level']" />
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5 mt-5">
    <x-item-box
        image-src="{{ asset('assets/images/icons/withdraw.webp') }}"
        title="Total Claimed Bonus"
        :valuesDolur="'$' . number_format($data['rtxPrice'] * $data['total_withdraw'], 3)"
        :values="number_format($data['total_withdraw'], 3) . ' RTX'"
        flex="flex-col" />

    <x-item-box
        image-src="{{ asset('assets/images/icons/staked-amount.webp') }}"
        title="Staked Amount"
        :valuesDolur="'$' . number_format($data['rtxPrice'] * $data['activeStake'], 3)"
        :values="number_format($data['activeStake'], 3) . ' RTX'"
        flex="flex-col" />

    <x-item-box
        image-src="{{ asset('assets/images/icons/compounded-amount.webp') }}"
        title="Compounded Amount"
        :valuesDolur="'$' . number_format($data['rtxPrice'] * ($data['compound_amount'] + $data['self_investment'] - $data['total_unstake_amount']), 3)"
        :values="number_format(($data['compound_amount'] + $data['self_investment'] - $data['total_unstake_amount']), 3) . ' RTX'"
        flex="flex-col" />

    <x-item-box
        image-src="{{ asset('assets/images/icons/total-income.webp') }}"
        title="Last Stake Bonus"
        :valuesDolur="'$' . number_format($data['rtxPrice'] * $data['user']['daily_roi'], 3)"
        :values="number_format($data['user']['daily_roi'], 3) . ' RTX'"
        flex="flex-col" />

    <x-item-box
        image-src="{{ asset('assets/images/icons/upline-bonus.webp') }}"
        title="Upline Bonus"
        :values="number_format($data['user']['direct_income'], 3) . ' RTX'"
        :valuesDolur="'$' . number_format($data['rtxPrice'] * $data['user']['direct_income'], 3)"
        flex="flex-col" />

    <x-item-box
        image-src="{{ asset('assets/images/icons/total-directs.webp') }}"
        title="Total Directs"
        :values="$data['user']['active_direct']"
        flex="flex-col" />

    <x-item-box
        image-src="{{ asset('assets/images/icons/direct-investment.webp') }}"
        title="Direct Investment"
        :valuesDolur="'$' . number_format($data['rtxPrice'] * $data['user']['direct_business'], 3)"
        :values="number_format($data['user']['direct_business'], 3) . ' RTX'"
        flex="flex-col" />

    <x-item-box
        image-src="{{ asset('assets/images/icons/total-team.webp') }}"
        title="Total Team"
        :values="$data['user']['my_team']"
        flex="flex-col" />

    <x-item-box
        image-src="{{ asset('assets/images/icons/total-team-investment.webp') }}"
        title="Team Investment"
        :valuesDolur="'$' . number_format(($data['rtxPrice'] * ($data['user']['my_business'] + $data['user']['strong_business'] + $data['user']['weak_business'])), 3)"
        :values="number_format(($data['user']['my_business'] + $data['user']['strong_business'] + $data['user']['weak_business']), 3) . ' RTX'"
        flex="flex-col" />

    <x-item-box
        image-src="{{ asset('assets/images/icons/club-bonus.webp') }}"
        title="Club Bonus"
        :valuesDolur="'$' . number_format($data['rtxPrice'] * $data['user']['club_bonus'], 3)"
        :values="number_format($data['user']['club_bonus'], 3) . ' RTX'"
        flex="flex-col" />

    <x-item-box
        image-src="{{ asset('assets/images/icons/reward-bonus.webp') }}"
        title="Reward Bonus"
        :valuesDolur="'$' . number_format($data['rtxPrice'] * $data['user']['reward_bonus'], 3)"
        :values="number_format($data['user']['reward_bonus'], 3) . ' RTX'"
        flex="flex-col" />

    <x-item-box
        image-src="{{ asset('assets/images/icons/star-bonus.webp') }}"
        title="Star Bonus"
        :valuesDolur="'$' . number_format($data['rtxPrice'] * $data['user']['rank_bonus'], 3)"
        :values="number_format($data['user']['rank_bonus'], 3) . ' RTX'"
        flex="flex-col" />

    <x-item-box
        image-src="{{ asset('assets/images/icons/level-bonus.webp') }}"
        title="Level Income"
        :valuesDolur="'$' . number_format($data['rtxPrice'] * $data['user']['level_income'], 3)"
        :values="number_format($data['user']['level_income'], 3) . ' RTX'"
        flex="flex-col" />

    <x-item-box
        image-src="{{ asset('assets/images/icons/daily-pool.webp') }}"
        title="Daily Pool Bonus"
        :valuesDolur="'$' . number_format($data['rtxPrice'] * $data['dailyPoolWinners'], 3)"
        :values="number_format($data['dailyPoolWinners'], 3) . ' RTX'"
        flex="flex-col" />

    <x-item-box
        image-src="{{ asset('assets/images/icons/monthly-pool.webp') }}"
        title="Monthly Pool Bonus"
        :valuesDolur="'$' . number_format($data['rtxPrice'] * $data['monthlyPoolWinners'], 3)"
        :values="number_format($data['monthlyPoolWinners'], 3) . ' RTX'"
        flex="flex-col" />
</div>


        <div class="flex items-center gap-3 mt-4">
            <div class="w-1 h-8 bg-gradient-to-b from-[#fac35d] to-[#FAD85D] rounded-full"></div>
            <h3 class="font-bold text-xl md:text-2xl">Rank Bonus Analytics</h3>
            <div class="flex-1 h-px bg-gradient-to-r from-[#322751] via-[#322751] to-transparent"></div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 overflow-hidden">
        {{--    <x-rank-analytics
                lable="Team ROI"
                title="Total Team ROI"
                :valuesDolur="'$' . number_format(($data['rtxPrice'] * $data['teamRoi']), 3)"
                :value="$data['teamRoi']"
                unit="RTX"
                bgColor="#ffa0b7"
                borderColor="#ffa0b7"
                imageSrc="{{ asset('assets/images/icons/strongteam.svg') }}" />--}}

           {{-- <x-rank-analytics
                lable="Rank"
                title="Rank Users Bonus"
                :valuesDolur="'$' . number_format(($data['rtxPrice'] * $data['user']['rank_bonus']), 3)"
                :value="0"
                unit="RTX"
                bgColor="#aea0ff"
                borderColor="#aea0ff"
                imageSrc="{{ asset('assets/images/icons/leaderearn.svg') }}" />--}}

            <x-rank-analytics
                lable="Count"
                title="Rank Users in Team"
                :value="$data['rankUser']"
                unit="Users"
                bgColor="#8be189"
                borderColor="#8be189"
                imageSrc="{{ asset('assets/images/icons/teamgrowth.svg') }}" />

            <x-rank-analytics
                lable="Count"
                title="Active Users"
                :value="$data['user']['active_team']"
                unit="Users"
                bgColor="#FF6B6B"
                borderColor="#FF6B6B"
                imageSrc="{{ asset('assets/images/icons/employee.svg') }}" />

            <x-rank-analytics
                lable="Non-Rank"
                title="Non-Rank Users"
                :value="$data['nonRankUser']"
                unit="Users"
                bgColor="#ffe1a0"
                borderColor="#ffe1a0"
                imageSrc="{{ asset('assets/images/icons/weakteam.svg') }}" />

        </div>

        @php
        $rankUser = $data['rankUser'] ?? 0;
        $nonRankUser = $data['nonRankUser'] ?? 0;
        $totalUsers = $rankUser + $nonRankUser;

        $rankPercentage = $totalUsers > 0 ? round(($rankUser / $totalUsers) * 100, 1) : 0;
        $nonRankPercentage = 100 - $rankPercentage; // ensures total = 100
        @endphp

        <!-- Bonus Distribution Analysis -->
        <div class="group relative gap-2 md:gap-4 bg-[#121222] border border-[#322751] rounded-xl px-4 py-6 overflow-hidden text-left text-white mt-2">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">Bonus Distribution Analysis</h3>
                        <p class="text-sm text-gray-400">Performance breakdown of your team structure</p>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <div class="text-center">
                        <p class="text-xs text-gray-400 mb-1">Rank Contribution</p>
                        <p class="text-sm font-medium text-emerald-400">{{ $rankPercentage }}%</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-400 mb-1">Non-Rank Contribution</p>
                        <p class="text-sm font-medium text-purple-400">{{ $nonRankUser == 0 ? "0" : $nonRankPercentage }}%</p>
                    </div>
                </div>
            </div>
            <div class="w-full h-full absolute top-0 left-0 opacity-10 p-0 z-0 pointer-events-none">
                <img src="{{ asset('assets/images/wavebgbox.svg') }}" alt="Bonus Distribution Analysis"
                    class="w-full h-full object-cover b mx-auto hue-rotate-[225deg]" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 overflow-hidden">
            <x-rank-analytics
                lable="Strong Leg Business"
                title="Highest performing leg"
                :valuesDolur="'$' . number_format(($data['rtxPrice'] * ($data['firstLeg'])), 3)"
                :value="($data['firstLeg']) . 'RTX'"
                bgColor="#009688"
                borderColor="#009688"
                imageSrc="{{ asset('assets/images/icons/rank.svg') }}" />

            <x-rank-analytics
                lable="Other Legs"
                title="Development opportunity"
                :valuesDolur="'$' . number_format(($data['rtxPrice'] * ($data['otherLeg'])), 3)"
                :value="($data['otherLeg']) . 'RTX'"
                bgColor="#93695a"
                borderColor="#93695a"
                imageSrc="{{ asset('assets/images/icons/teamgrowthlaps.svg') }}" />

        </div>

        <div class="grid grid-cols-1 xl:grid-cols-1 gap-5 mt-4">
            <div class="cols-span-1 md:col-span-1 grid grid-cols-1">
                <div class="w-full xl:col-span-2 p-4 md:p-5 bg-[#171531] border border-[#845dcb] rounded-xl">
                    @include('components.packages-table')
                </div>
            </div>
        </div>
    </div>
</section>
<x-affiliate-modal
    link="https://{{ request()->getHost() }}/connect-wallet?sponser_code={{Session::get('wallet_address')}}"
    referrer="{{$data['sponser']['wallet_address']}}"
    :paths="['/my/stake', '/my/lpbonds', '/my/stablebonds']" />

<script src="{{asset('web3/ethers.umd.min.js')}}"></script>

<script src="{{asset('web3/web3.min.js')}}"></script>

<script>
    @if(!Session::has('admin_user_id'))

    async function checkWalletAddress() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        // Get the stored address from wherever it's stored (e.g., local storage)
        var storedAddress = "{{ Session::get('wallet_address') }}"

        // Get the connected wallet address
        var addressConnected = await window.ethereum.request({
            method: 'eth_requestAccounts'
        }); // Replace with your code to get the connected address

        // Compare the stored and connected addresses
        if (storedAddress.toLowerCase() !== addressConnected[0].toLowerCase()) {
            // Call your function or perform the desired action
            // handleAccountChange(addressConnected); // Replace with the function you want to call
            showToast("error", "Wallet Address Mismatch! Please connect the correct wallet address.");

            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            // Add CSRF token
            var token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_token';
            token.value = csrfToken;
            form.appendChild(token);

            document.body.appendChild(form);
            setTimeout(function() {
                form.submit();
            }, 300);
        }
    }

    setInterval(checkWalletAddress, 1500); // Call checkWalletAddress() every 5 seconds (5000 milliseconds)

    @endif
</script>

@endsection

<script src="{{asset('assets/js/apexcharts.js')}}"></script>