Motivation behind this experiment @ http://attozk.mine.pk/posts/404-php-dns-server-benchmarking-vs-pdns-mysql


# Spinning up DNS Servers

There are three dns servers included:

* `server-react-echo.php` Written with [PHPReact/Dns](https://github.com/reactphp/dns) library, Simple ECHO DNS Server which parses binary question and returns a binary response, without any answers. This server runs both on UDP & TCP.

* `server-udp-raw-echo.php` Similar to ECHO DNS server but written with `stream_socket_server` & `stream_socket_recvfrom` which parses binary question and returns a binary response without any answers. This server runs only on UDP.

* `server-react-pdns-mysql.php` Written with [PHPReact/Dns](https://github.com/reactphp/dns) library, this DNS server parses binary question, looks it up in Mysql and returns a binary response. This is not the exact implementation of PDNS+Mysql, but works for this experiment. This server also runs on UDP & TCP. Unlike PDNS there is no caching layer.

To start php dns servers:

```bash
cd experiment

php server-react-echo.php &
php server-react-pdns-mysql.php &
php server-udp-raw-echo.php &
```

# Benchmarking

This is not a scientific test by any mean but more of a general stress test to see how does `server-react-pdns-mysql.php` perform relatively against PDNS+Mysql.

Run dnsperf (ideally from a different server) to test DNS servers.

```
# testing PDNS+Mysql
dnsperf -d dnsperf-query-split-100K -s SERVERIP -p 53 -v

# testing PHP React Echo DNS server (TCP + UDP)
dnsperf -d dnsperf-query-split-100K -s SERVERIP -p 553 -v

# testing PHP Raw DNS server using stream_socket_recvfrom (UDP only)
dnsperf -d dnsperf-query-split-100K -s SERVERIP -p 555 -v

# testing PHP React Pdns Alike DNS Server (TCP + UDP)
dnsperf -d dnsperf-query-split-100K -s SERVERIP -p 554 -v

if you don't have splitted files on remote server, then use /usr/share/dnsperf/queryfile-example-current instead.
```

# Results

Your result may vary depending upon the server & network load. My specs were as following:

* PowerDNS Authoritative Server 3.3.1 (non-recursive, default settings with mysql backend)
* MariaDB Server 10.0.10
* Centos 6.5:

```
# free -m
             total       used       free     shared    buffers     cached
Mem:           490        421         68          0         34        180
-/+ buffers/cache:        206        283
Swap:            0          0          0

# more /proc/cpuinfo
processor       : 0
vendor_id       : GenuineIntel
cpu family      : 6
model           : 2
model name      : QEMU Virtual CPU version 1.0
stepping        : 3
cpu MHz         : 3400.024
cache size      : 4096 KB
fpu             : yes
fpu_exception   : yes
cpuid level     : 4
wp              : yes
flags           : fpu de pse tsc msr pae mce cx8 apic sep mtrr pge mca cmov pse36 clflush mmx fxsr sse sse2 syscall
nx lm up rep_good unfair_spinlock pni vmx cx16 popcnt hypervisor lahf_lm
bogomips        : 6800.04
clflush size    : 64
cache_alignment : 64
address sizes   : 40 bits physical, 48 bits virtual
power management:
```

Results against PDNS+Mysql:

```
# dnsperf -d dnsperf-query-split-100K -s testingserver -p 53 -v

Statistics:

  Queries sent:         100000
  Queries completed:    99878 (99.88%)
  Queries lost:         122 (0.12%)

  Response codes:       NOERROR 99860 (99.98%), SERVFAIL 17 (0.02%), NXDOMAIN 1 (0.00%)
  Average packet size:  request 38, response 38
  Run time (s):         140.788780
  Queries per second:   709.417327

  Average Latency (s):  0.133424 (min 0.121398, max 0.158741)
  Latency StdDev (s):   0.005155
```

Results against `server-react-echo.php`

```
# dnsperf -d dnsperf-query-split-100K -s testingserver -p 553 -v

Statistics:

  Queries sent:         100000
  Queries completed:    99927 (99.93%)
  Queries lost:         73 (0.07%)

  Response codes:       NOERROR 99927 (100.00%)
  Average packet size:  request 38, response 38
  Run time (s):         134.276466
  Queries per second:   744.188486

  Average Latency (s):  0.129195 (min 0.121362, max 0.148611)
  Latency StdDev (s):   0.005408
```

Results against `server-udp-raw-echo.php`

```
# dnsperf -d dnsperf-query-split-100K -s testingserver -p 555 -v

Statistics:

  Queries sent:         100000
  Queries completed:    99998 (100.00%)
  Queries lost:         2 (0.00%)

  Response codes:       NOERROR 99998 (100.00%)
  Average packet size:  request 38, response 38
  Run time (s):         143.006352
  Queries per second:   699.255653

  Average Latency (s):  0.141373 (min 0.134037, max 0.157433)
  Latency StdDev (s):   0.003953
```

Results against `server-react-pdns-mysql.php`

```
# dnsperf -d dnsperf-query-split-100K -s testingserver -p 554 -v

Statistics:

  Queries sent:         100000
  Queries completed:    99999 (100.00%)
  Queries lost:         1 (0.00%)

  Response codes:       NOERROR 99999 (100.00%)
  Average packet size:  request 38, response 38
  Run time (s):         147.829531
  Queries per second:   676.448064

  Average Latency (s):  0.146202 (min 0.140409, max 0.189567)
  Latency StdDev (s):   0.003696
```

# Conclusion
I am interested mostly in `server-react-pdns-mysql.php` in which I do get PDNS like results.
`server-react-pdns-mysql.php` takes more CPU but looking at the results of `server-react-echo.php` there is room
for improvement.

I would continue with building this (Babaji DNS) server in PHP.
