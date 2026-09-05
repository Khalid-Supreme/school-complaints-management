<template>
  <SplashScreen v-if="!isIPhone" :config="splashConfig" @complete="handleComplete" />
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import SplashScreen from '../components/SplashScreen.vue';

const router = useRouter();
const isIPhone = ref(false);

onMounted(() => {
  const ua = navigator.userAgent || '';
  const isIOS = /iPhone|iPad|iPod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  if (isIOS) {
    isIPhone.value = true;
    router.replace('/login');
  }
});

const splashConfig = {
  logo: '/images/alhikmah_logo.png',
  // projectTitle: 'CAMPUS VOICE',
  projectTitle: 'Developing a Web-Based Complaint System with Integrated Data Encryption and Intrusion Prevention',
  // subtitle: 'Developing a Web-Based Complaint System with Integrated Data Encryption and Intrusion Prevention',
  authorName: 'Khalid Razak Oluremi',
  studentID: '2203CYBTU1011',
  description: 'A Final Year Project submitted in partial fulfillment of the requirements for the award of the Bachelor of Science Degree in Cybersecurity.',
  department: 'Department of Computer Science,',
  faculty: 'Faculty of Computing, Engineering and Technology,',
  university: 'Al-Hikmah University,',
  location: 'Ilorin-Nigeria.',
  date: 'JULY, 2025',
  timerDuration: 100000,
  showProgress: true,
  showProceedButton: true
};

const handleComplete = () => {
  router.push('/login');
};
</script>
