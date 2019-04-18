local ffi = require("ffi")
ffi.cdef[[
    struct timeval {
        long int tv_sec;
        long int tv_usec;
    };
    int gettimeofday(struct timeval *tv, void *tz);
    int access(const char *filename,int amode);  
]]; 

return ffi
