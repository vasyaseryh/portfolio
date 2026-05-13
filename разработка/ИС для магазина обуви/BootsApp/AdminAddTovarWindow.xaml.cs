using Microsoft.Win32;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Runtime.Remoting.Contexts;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.IO;

namespace BootsApp
{
    /// <summary>
    /// Логика взаимодействия для AdminAddTovarWindow.xaml
    /// </summary>
    public partial class AdminAddTovarWindow : Window
    {
        bootsEntities2 _context;
        public bool IsEdit;
        public string ImagePath;

        public AdminAddTovarWindow()
        {
            InitializeComponent();
            IsEdit = false;
            _context = bootsEntities2.GetContext();
            DataContext = new Tovar();
            
        }

        public AdminAddTovarWindow(Tovar tovar)
        {
            InitializeComponent();
            IsEdit = true;
            _context = bootsEntities2.GetContext();
            DataContext = tovar;
    

        }

       private void SelectImage_Click(object sender, RoutedEventArgs e)
        {
            OpenFileDialog opd = new OpenFileDialog();
            opd.Filter = "Image Files|*.jpg;*.jpeg;*.png;*.bmp";
            if (opd.ShowDialog() == true) 
            {
                ImagePath = opd.FileName;
                txtКартинка.Text = Path.GetFileName(ImagePath);
            }
        }

        private void ChangeButt(object sender, RoutedEventArgs e)
        {
           
            if (!string.IsNullOrEmpty(ImagePath)) 
            {

                string projectPath = Directory.GetParent(AppDomain.CurrentDomain.BaseDirectory).Parent.Parent.FullName;
                string folderPath = Path.Combine(projectPath, "IMG");
                File.Copy(ImagePath, Path.Combine(folderPath, Path.GetFileName(ImagePath)), true);
                
            }


            if (IsEdit)
            {
                var tovar = DataContext as Tovar;
                tovar.Фото = Path.GetFileName(ImagePath);
                _context.SaveChanges();
            }
            else 
            {
                var newTovar = (Tovar)DataContext;
                newTovar.Фото = txtКартинка.Text;
                _context.Tovars.Add(newTovar);
                _context.SaveChanges();
            }
            this.Close();
        }

 
    }
}
