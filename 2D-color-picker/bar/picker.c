#include <stdio.h>
#include <stdlib.h>

#define QUEUE_SIZE 800

int xs, ys, shades;
unsigned char *buffer;

void plot(int x, int y, int r, int g, int b)
{
    buffer[(y*xs+x)*3+0] = (256/shades)*r;
    buffer[(y*xs+x)*3+1] = (256/shades)*g;
    buffer[(y*xs+x)*3+2] = (256/shades)*b;
}

int queue[QUEUE_SIZE][3];
int size = 0;

void addToQueue(int r, int g, int b)
{
    queue[size][0] = r;
    queue[size][1] = g;
    queue[size][2] = b;
    ++size;
}

int getSaturation(int i)
{
    float r, g, b, min, max, delta, l, h, s, dr, dg, db;
    r = queue[i][0]*1.0/(float)shades;
    g = queue[i][1]*1.0/(float)shades;
    b = queue[i][2]*1.0/(float)shades;
    if(r<g)
    {
        min = r;
        max = g;
    }
    else
    {
        min = g;
        max = r;
    }
    if(b<min) min = b;
    if(b>max) max = b;
    delta = max-min;
    l = (max+min)/2.0;
    if(delta==0)
    {
        h = 0.0;
        s = 0.0;
    }
    else
    {
        if (l < 0.5) s = delta / (max+min);
        else s  = delta / (2-max-min);
        dr = (((max-r)/6)+(max/2))/max;
        dg = (((max-g)/6)+(max/2))/max;
        db = (((max-b)/6)+(max/2))/max;
        if (r==max) h = db-dg;
        else if (g==max) h = 0.333+dr-db;
        else if (b==max) h == 0.667+dg-dr;
        if (h<0.0) h+=1.0;
        if (h>1.0) h-=1.0;
    }
    return s*shades;
}

int metric(int i)
{
    /* rgb */ // return 0;
    /* sumsquares */ // return(queue[i][0]*queue[i][0]+queue[i][1]*queue[i][1]+queue[i][2]*queue[i][2]);
    /* step2 */ // return((queue[i][2]<queue[i][1])*2 + (queue[i][2]>queue[i][0])*4 + (queue[i][1]<queue[i][0]));
    /* step3 */ // return((queue[i][2]<queue[i][1])*1 + (queue[i][2]<queue[i][0])*4 + (queue[i][1]>queue[i][0])*2);
    /* saturation */ return getSaturation(i);
}

void sortQueue()
{
    int i, j, t, k;

    for(i=0; i<size-1; ++i)
    {
        for(j=i+1; j<size; ++j)
        {
            if(metric(i)>metric(j))
            {
                for(k=0; k<3; ++k)
                {
                    t = queue[i][k]; queue[i][k] = queue[j][k]; queue[j][k] = t;
                }
            }
        }
    }
}

void plotQueue(int x, int fill)
{
    int i, c=0, d=0;
    for(i=0; i<fill; ++i)
    {
        plot(x, ys-i-1, queue[c][0], queue[c][1], queue[c][2]);
        d += size;
        if(d>=fill)
        {
            d -= fill;
            ++c;
        }
    }
}

int main(void)
{
    int light, r, g, b, t;
    
    shades = 64;
    xs = (shades-1)*10+1;
    ys = 800;
    
    buffer = (unsigned char *)calloc(xs*ys*3, 1);
    fprintf(stdout, "P6\n%d %d\n255\n", xs, ys);

    for(light=0; light<=(shades-1)*10; ++light)
    {
        size = 0;
        for(r=0; r<shades; ++r)
        {
            for(g=0; g<shades; ++g)
            {
                for(b=0; b<shades; ++b)
                {
                    if(r*3+g*6+b==light)
                    {
                        addToQueue(r, g, b);
                    }
                }
            }
        }
        sortQueue();
        plotQueue(light, ys);
    }
    
    fwrite(buffer, 1, xs*ys*3, stdout);
    free(buffer);
}
